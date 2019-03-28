var _ = require('lodash');
var pathToRegexp = require('path-to-regexp');
var { parse, Visitor, AST } = require('json-ast');
var { commentParser, mockStringParser, mockNumberParser } = require('./parser');

var swagger = {
	swagger	: "2.0",
	info	: {},
	paths	: {},
	definitions: {}
};

function toSwagger(apidocJson, projectJson) {
	swagger.info = addInfo(projectJson);
	swagger.paths = extractPaths(apidocJson);
	return swagger;
}

var tagsRegex = /(<([^>]+)>)/ig;
// Removes <p> </p> tags from text
function removeTags(text) {
	return text ? text.replace(tagsRegex, "") : text;
}

function addInfo(projectJson) {
	var info = {};
	info["title"] = projectJson.title || projectJson.name;
	info["version"] = projectJson.version;
	info["description"] = projectJson.description;
	return info;
}

/**
 * Extracts paths provided in json format
 * post, patch, put request parameters are extracted in body
 * get and delete are extracted to path parameters
 * @param apidocJson
 * @returns {{}}
 */
function extractPaths(apidocJson){
	var apiPaths = groupByUrl(apidocJson);
	var paths = {};
	for (var i = 0; i < apiPaths.length; i++) {
		var verbs = apiPaths[i].verbs;
		var url = verbs[0].url;
		var pattern = pathToRegexp(url, null);
		var matches = pattern.exec(url);

		// Surrounds URL parameters with curly brackets -> :email with {email}
		var pathKeys = [];
		for (var j = 1; j < matches.length; j++) {
			var key = matches[j].substr(1);
			url = url.replace(matches[j], "{"+ key +"}");
			pathKeys.push(key);
		}

		for(var j = 0; j < verbs.length; j++) {
			var verb = verbs[j];
			var type = verb.type;

			var obj = paths[url] = paths[url] || {};

			if (type == 'post' || type == 'patch' || type == 'put') {
				_.extend(obj, createPostPushPutOutput(verb, swagger.definitions, pathKeys));
			} else {
				_.extend(obj, createGetDeleteOutput(verb, swagger.definitions));
			}
		}
	}
	return paths;
}

class CommentsVisitor extends Visitor {
  constructor() {
    super();
    this.comments = [];
  }
 
  comment(commentNode) {
    this.comments.push(commentNode);
  }
};

function translateInfoWithMock (type, value, mock) {
	// console.info(type, value, mock)
	let info = {}
	switch (type) {
		case 'number':
		    {
				// 有设置mock数据类型时，优先使用设置的值
				let { _mock, _minimum, _maximum, _enum, _type } = mockNumberParser(type, value, mock)
				_mock != null ? info['mock'] = {mock: _mock} : null
				_minimum != null ? info['minimum'] = _minimum : null
				_maximum != null ? info['maximum'] = _maximum : null
				_enum != null ? info['enum'] = _enum : null
				_type != null ? info['type'] = _type : null

				info['default'] = value
			}
			break
		case 'string':
			{
				info['type'] = 'string'
				// 有设置mock数据类型时，优先使用设置的值
				let { _mock, _minLength, _maxLength, _enum } = mockStringParser(type, value, mock)
				_mock != null ? info['mock'] = {mock: _mock} : null
				_minLength != null ? info['minLength'] = _minLength : null
				_maxLength != null ? info['maxLength'] = _maxLength : null
				_enum != null ? info['enum'] = _enum : null

				if (value.length > 0 && value.length < 20) {
					info['default'] = value
				}
			}
			break
		case 'true':
			info['type'] = 'boolean'
			info['default'] = true
			break
		case 'false':
			info['type'] = 'boolean'
			info['default'] = false
			break
		case 'array':
			info['minItems'] = 0
			info['maxItems'] = 21
			info['uniqueItems'] = true
		  break
	}

	return info
}

function scanTree (tree, comments) {
	if (tree instanceof AST.JsonString ||
		tree instanceof AST.JsonNumber ||
		tree instanceof AST.JsonTrue ||
		tree instanceof AST.JsonFalse
	) {
		// 基本类型
		return {
			type: tree._type
		}

	} else if (tree instanceof AST.JsonComment) {
		// 注释
		return tree._value

	} else if (tree instanceof Array) {
		// 数组
		return tree.map((item, index) => {
			return scanTree(item, comments)
		})

	} else if (tree instanceof AST.JsonArray) {
		// 数组
		let items = scanTree(tree._items, comments)
		let item = items.length > 0 ? items[0] : {}
		return {
			type: tree._type,
			items: item
		}

	} else if (tree instanceof AST.JsonObject) {
		// 对象
		let properties = {}
		scanTree(tree._properties, comments).forEach(item => {
			properties = Object.assign(properties, item)
		})
		let required = []
		Object.keys(properties).map(key => {
			if (properties[key].internalRequired) {
				required.push(key)
			}
			delete properties[key].internalRequired
		})

		return {
			'type': 'object',
			'required': required,
			'properties': properties
		}

	} else if (tree instanceof AST.JsonProperty) {
		// 属性
		let key = scanTree(tree._key, comments)
		let value = scanTree(tree._value, comments)
		// required、mock、comment由外层处理
		let { _required, _comment, _mock } = commentParser(comments, tree._key._position._start.line)
		let obj = {}
		obj[key] = Object.assign(value, {
			internalRequired: _required,
			description: _comment,
		}, translateInfoWithMock(value.type, tree._value.value, _mock))
		return obj

	} else if (tree instanceof AST.JsonKey) {
		return tree._value
	} else if (tree instanceof AST.JsonDocument) {
		return scanTree(tree._child, comments)
	} else if (tree instanceof AST.JsonNull) {
		console.info('不建议使用null值')
		return {
			'type': 'object'
		}
	} else if (tree instanceof AST.JsonValue) {
		console.info('存在未知类型1')
		console.info(tree)
	} else if (tree instanceof AST.JsonNode) {
		console.info('存在未知类型2')
		console.info(tree)
	} else {
		return tree
	}
}

function createApiSuccessExampleOutput(verbs) {
	if (verbs && verbs.success && verbs.success.examples instanceof Array && verbs.success.examples.length > 0) {
		let examples = verbs.success.examples[0]
		let successExamples = verbs.success.examples.filter(item => item.title.includes('Success-Response'))
		if (successExamples instanceof Array && successExamples.length > 0) {
			examples = successExamples[0]
		}

		if (examples.type === 'json' || examples.type === 'json-original') {
			if (examples.content.indexOf('{') >= 0 && examples.content.lastIndexOf('}') >= 0) {
				// json转换为树形结构
				let content = examples.content.substring(examples.content.indexOf('{'), examples.content.lastIndexOf('}') + 1)
				let tree = null
				try {
					tree = parse(content)
				} catch (e) {
					console.info(content)
					console.info('JSON解析异常：' + verbs.filename)
				}
				let commentsVisitor = new CommentsVisitor()
				commentsVisitor.visit(tree)
				let result = scanTree(tree, commentsVisitor.comments)

				if (examples.type === 'json') {
					if (result.properties && result.properties.code && result.properties.msg) {
						return result
					} if (result.properties && result.properties.ret_code && result.properties.ret_msg) {
						return result
					} else {
						return {
							type: "object",
							required: ['code', 'msg', 'data'],
							properties: {
								code: {
									type: "number",
									description: "错误码",
									mock: {
										mock: 0
									}
								},
								msg: {
									type: "string",
									description: "错误信息",
									mock: {
										mock: "OK"
									}
								},
								data: result
							}
						}
					}
				} else if (examples.type === 'json-original') {
					return result
				}
			} else {
				return {
					type: "object",
					properties: {}
				}
			}
		} else if (examples.type === 'string') {
			let content = examples.content.trim().replace(/HTTP\/\d\.\d(\w|\W)+OK\s+/gi, '')
			if (content.indexOf('//') >= 0) {
				content = content.substring(content.indexOf('//') + '//'.length).trim()
				let { _comment } = commentParser(content)
				return {
					type: "string",
					description: _comment,
				}
			} else {
				return {
					type: "string",
					description: "",
				}
			}
		}
	}
}

function createPostPushPutOutput(verbs, definitions, pathKeys) {
	var pathItemObject = {};
	var verbDefinitionResult = createVerbDefinitions(verbs,definitions);

	var params = [];
	var pathParams = createPathParameters(verbs, pathKeys);
	pathParams = _.filter(pathParams, function(param) {
		var hasKey = pathKeys.indexOf(param.name) !== -1;
		return !(param.in === "path" && !hasKey)
	});

	params = params.concat(pathParams);
	var required = verbs.parameter && verbs.parameter.fields && 
					verbs.parameter.fields.Parameter && verbs.parameter.fields.Parameter.length > 0;
	
	params.push({
		"in": "body",
		"name": "body",
		"description": removeTags(verbs.description),
		"required": required,
		"schema": {
			"$ref": "#/definitions/" + verbDefinitionResult.topLevelParametersRef
		}
	});

	pathItemObject[verbs.type] = {
		tags: [verbs.group],
		summary: removeTags(verbs.title),
		consumes: [
			"application/json"
		],
		produces: [
			"application/json"
		],
		parameters: params
	}

	let response = createApiSuccessExampleOutput(verbs)
	pathItemObject[verbs.type].responses = {
		"200": {
			"description": "successful operation",
			"schema": response
		}
	};

	// if (verbDefinitionResult.topLevelSuccessRef) {
	// 	pathItemObject[verbs.type].responses = {
  //         "200": {
  //           "description": "successful operation",
  //           "schema": {
  //             "type": verbDefinitionResult.topLevelSuccessRefType,
  //             "items": {
  //               "$ref": "#/definitions/" + verbDefinitionResult.topLevelSuccessRef
  //             }
  //           }
  //         }
  //     	};
	// };
	
	return pathItemObject;
}

function createVerbDefinitions(verbs, definitions) {
	var result = {
		topLevelParametersRef : null,
		topLevelSuccessRef : null,
		topLevelSuccessRefType : null
	};
	var defaultObjectName = verbs.name;

	var fieldArrayResult = {};
	if (verbs && verbs.parameter && verbs.parameter.fields) {
		fieldArrayResult = createFieldArrayDefinitions(verbs.parameter.fields.Parameter, definitions, verbs.name, defaultObjectName, verbs);		
		result.topLevelParametersRef = fieldArrayResult.topLevelRef;
	};

	if (verbs && verbs.success && verbs.success.fields) {
		fieldArrayResult = createFieldArrayDefinitions(verbs.success.fields["Success 200"], definitions, verbs.name, defaultObjectName, verbs);		
		result.topLevelSuccessRef = fieldArrayResult.topLevelRef;
		result.topLevelSuccessRefType = fieldArrayResult.topLevelRefType;
	};

	return result;
}

function createFieldArrayDefinitions(fieldArray, definitions, topLevelRef, defaultObjectName, verbs) {
	var result = {
		topLevelRef : topLevelRef,
		topLevelRefType : null
	}

	if (!fieldArray) {
		return result;
	}

	for (var i = 0; i < fieldArray.length; i++) {
		var parameter = fieldArray[i];

		var nestedName = createNestedName(parameter.field);
		var objectName = nestedName.objectName;
		if (!objectName) {
			objectName = defaultObjectName;
		}
		var type = parameter.type;
		if (!type) {
			console.info('JSON解析异常：无类型 ' + verbs.filename)
			return
		}
		if (i == 0) {
			result.topLevelRefType = type;
			if(parameter.type == "Object") {
				objectName = nestedName.propertyName;
				nestedName.propertyName = null;
			} else if (parameter.type == "Array") {
				objectName = nestedName.propertyName;
				nestedName.propertyName = null;				
				result.topLevelRefType = "array";
			}
			result.topLevelRef = objectName;
		};

		definitions[objectName] = definitions[objectName] ||
			{ properties : {}, required : [] };

		if (nestedName.propertyName) {
			var prop = { type: (parameter.type || "").toLowerCase(), description: removeTags(parameter.description) };
			if(parameter.type == "Object") {
				prop.$ref = "#/definitions/" + parameter.field;
			}

			var typeIndex = type.indexOf("[]");
			if(typeIndex !== -1 && typeIndex === (type.length - 2)) {
				prop.type = "array";
				prop.items = {
					type: type.slice(0, type.length-2)
				};
			}

			definitions[objectName]['properties'][nestedName.propertyName] = prop;
			if (!parameter.optional) {
				var arr = definitions[objectName]['required'];
				if(arr.indexOf(nestedName.propertyName) === -1) {
					arr.push(nestedName.propertyName);
				}
			};

		};
	}

	return result;
}

function createNestedName(field) {
	var propertyName = field;
	var objectName;
	var propertyNames = field.split(".");
	if(propertyNames && propertyNames.length > 1) {
		propertyName = propertyNames[propertyNames.length-1];
		propertyNames.pop();
		objectName = propertyNames.join(".");
	}

	return {
		propertyName: propertyName,
		objectName: objectName
	}
}


/**
 * Generate get, delete method output
 * @param verbs
 * @returns {{}}
 */
function createGetDeleteOutput(verbs,definitions) {
	var pathItemObject = {};
	verbs.type = verbs.type === "del" ? "delete" : verbs.type;
	var verbDefinitionResult = createVerbDefinitions(verbs,definitions);
	pathItemObject[verbs.type] = {
		tags: [verbs.group],
		summary: removeTags(verbs.title),
		consumes: [
			"application/json"
		],
		produces: [
			"application/json"
		],
		parameters: createPathParameters(verbs)
	}

	let response = createApiSuccessExampleOutput(verbs)
	pathItemObject[verbs.type].responses = {
		"200": {
			"description": "successful operation",
			"schema": response
		}
	};

	// if (verbDefinitionResult.topLevelSuccessRef) {
	// 	pathItemObject[verbs.type].responses = {
  //         "200": {
  //           "description": "successful operation",
  //           "schema": {
  //             "type": verbDefinitionResult.topLevelSuccessRefType,
  //             "items": {
  //               "$ref": "#/definitions/" + verbDefinitionResult.topLevelSuccessRef
  //             }
  //           }
  //         }
  //     	};
	// };
	return pathItemObject;
}

/**
 * Iterate through all method parameters and create array of parameter objects which are stored as path parameters
 * @param verbs
 * @returns {Array}
 */
function createPathParameters(verbs, pathKeys) {
	pathKeys = pathKeys || [];

	var pathItemObject = [];
	var defaultIn = null
	if (['get', 'delete'].some(method => verbs.type.toLowerCase() === method)) {
		defaultIn = 'query'
	} else if (['post', 'put', 'patch'].some(method => verbs.type.toLowerCase() === method)) {
		defaultIn = 'body'
	}

	if (verbs.parameter) {
		for (var group in verbs.parameter.fields) {
			var item = verbs.parameter.fields[group];
			if (item) {
				for (var i = 0; i < item.length; i++) {
					var param = item[i];
					var field = param.field;
					var type = param.type;
					if (!type) {
						console.info('JSON解析异常：无类型 ' + verbs.filename)
					} else {
						pathItemObject.push({
							name: field,
							in: type === "file" ? "formData" : group !== 'Parameter' ? group : defaultIn,
							required: !param.optional,
							type: param.type.toLowerCase(),
							description: removeTags(param.description)
						});
					}
				}
			}
		}
	}
	return pathItemObject;
}

function groupByUrl(apidocJson) {
	return _.chain(apidocJson)
		.groupBy("url")
		.pairs()
		.map(function (element) {
			return _.object(_.zip(["url", "verbs"], element));
		})
		.value();
}

module.exports = {
	toSwagger: toSwagger
};