var _ = require('lodash');
var pathToRegexp = require('path-to-regexp');
var jsoncParser = require('jsonc-parser');
var { parse, Visitor, AST } = require('json-ast');

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
    this.comments.push(commentNode.value);
  }
};

function scanTree(tree, comments) {
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
			console.info('------start--------')
			console.info(item)
			console.info(comments)
			if (comments) {
				comments.map(comment => {
					console.info(comment._position._start.line)
				})
			}
			console.info('------end-------')
			// 匹配同行注释

			return scanTree(item, comments ? comments[index] : '')
		})

	} else if (tree instanceof AST.JsonArray) {
		// 数组
		let items = scanTree(tree._items)
		let item = items.length > 0 ? items[0] : {}
		return {
			type: tree._type,
			items: item
		}

	} else if (tree instanceof AST.JsonObject) {

		let properties = {}
		scanTree(tree._properties, tree._comments).forEach(item => {
			properties = Object.assign(properties, item)
		})
		let required = []
		Object.keys(properties).map(key => {
			if (properties[key].required) {
				required.push(key)
			}
			delete properties[key].required
		})
		return {
			'type': 'object',
			'required': required,
			'properties': properties
		}

	} else if (tree instanceof AST.JsonProperty) {

		let key = scanTree(tree._key)
		let value = scanTree(tree._value)
		let comment = scanTree(comments)
		comment = comment ? comment.trim() : ''
		let required = true
		let symbol = '[]'
		if (comment.indexOf(symbol) === 0) {
			required = false
			comment = comment.substring(comment.indexOf(symbol) + symbol.length).trim()
		}
		// required和comment由外层处理
		let obj = {}
		obj[key] = Object.assign(value, {
			required: required,
			description: comment
		})
		return obj

	} else if (tree instanceof AST.JsonKey) {
		
		return tree._value

	} else if (tree instanceof AST.JsonDocument) {

		return scanTree(tree._child)

	} else if (tree instanceof AST.JsonValue) {
		throw new Error('未捕获类型')

	} else if (tree instanceof AST.JsonNode) {
		throw new Error('未捕获类型')

	} else {
		return tree
	}
}

function createApiSuccessExampleOutput(verbs, definitions, pathKeys) {
	if (verbs.success.examples instanceof Array && verbs.success.examples.length > 0) {
		let examples = verbs.success.examples[0]
		let successExamples = verbs.success.examples.filter(item => item.title.includes('Success-Response'))
		if (successExamples instanceof Array && successExamples.length > 0) {
			examples = successExamples[0]
		}

		if (examples.content.indexOf('{') >= 0 && examples.content.lastIndexOf('}') >= 0) {
			let content = examples.content.substring(examples.content.indexOf('{'), examples.content.lastIndexOf('}') + 1)
			// content = jsoncParser.stripComments(content)
			let tree = parse(content)
			let commentsVisitor = new CommentsVisitor()
			commentsVisitor.visit(tree)
			console.info(commentsVisitor.comments)
			let result = scanTree(tree, content)
			console.info('*************')
			console.info(JSON.stringify(result))
			return result
		}
	}
}

function createPostPushPutOutput(verbs, definitions, pathKeys) {

	let response = createApiSuccessExampleOutput(verbs, definitions, pathKeys)

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
		summary: removeTags(verbs.description),
		consumes: [
			"application/json"
		],
		produces: [
			"application/json"
		],
		parameters: params
	}

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
		fieldArrayResult = createFieldArrayDefinitions(verbs.parameter.fields.Parameter, definitions, verbs.name, defaultObjectName);		
		result.topLevelParametersRef = fieldArrayResult.topLevelRef;
	};

	if (verbs && verbs.success && verbs.success.fields) {
		fieldArrayResult = createFieldArrayDefinitions(verbs.success.fields["Success 200"], definitions, verbs.name, defaultObjectName);		
		result.topLevelSuccessRef = fieldArrayResult.topLevelRef;
		result.topLevelSuccessRefType = fieldArrayResult.topLevelRefType;
	};

	return result;
}

function createFieldArrayDefinitions(fieldArray, definitions, topLevelRef, defaultObjectName) {
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
		summary: removeTags(verbs.description),
		consumes: [
			"application/json"
		],
		produces: [
			"application/json"
		],
		parameters: createPathParameters(verbs)
	}
	if (verbDefinitionResult.topLevelSuccessRef) {
		pathItemObject[verbs.type].responses = {
          "200": {
            "description": "successful operation",
            "schema": {
              "type": verbDefinitionResult.topLevelSuccessRefType,
              "items": {
                "$ref": "#/definitions/" + verbDefinitionResult.topLevelSuccessRef
              }
            }
          }
      	};
	};
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