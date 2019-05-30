var validator = require('validator')

function mockInfoParser (source) {
	let mock = ''
	let rest = ''
	if (/^\{.*\}.*$/.test(source)) {
		mock = source.substring(source.indexOf('{') + 1, source.indexOf('}')).trim()
		rest = source.substring(source.indexOf('}') + 1).trim()
		return {
			mock,
			rest
		}
	} else {
		rest = source
		return {
			mock,
			rest
		}
	}
}

function requiredParser (source) {
	let required = true
	let rest = ''

	if (/^\[\].*/.test(source)) {
		required = false
		rest = source.substring(source.indexOf(']') + 1).trim()
	} else {
		required = true
		rest = source.trim()
	}

	return {
		required,
		rest
	}
}

function mockNumberParser (type, value, mock) {
	let _mock = null
	let _minimum = null
	let _maximum = null
	let _enum = null
	let _type = null

	if (/^\-{0,1}\d*\.\d*$/.test(value)) {
		_type = 'number'
	} else {
		_type = 'integer'
	}

	if (value.indexOf('-') === 0) {
		_minimum = -9999
		_maximum = 9999
	} else {
		_minimum = 0
		_maximum = 9999
	}

	// 拆分
	let before = ''
	let after = ''
	if (/^.*\=.*$/.test(mock)) {
		before = mock.substring(0, mock.indexOf('='))
		after = mock.substring(mock.indexOf('=') + 1)
	} else if (/^.*\.\..*$/.test(mock)) {
		before = mock
	} else if (/^[\"].*[\"]$/.test(mock)) {
		after = mock
	}

	// 解析before
	if (/^.*\.\..*$/.test(before)) {
		lengthArray = before.split('..')
		lengthArray[0] ? _minimum = parseInt(lengthArray[0]) : null
		lengthArray[1] ? _maximum = parseInt(lengthArray[1]) : null
	}

	return {
		_mock,
		_minimum,
		_maximum,
		_enum,
		_type
	}
}

function mockStringParser (type, value, mock) {
	let _mock = null
	let _minLength = null
	let _maxLength = null
	let _enum = null

	// 拆分
	let before = ''
	let after = ''
	if (/^.*\=.*$/.test(mock)) {
		before = mock.substring(0, mock.indexOf('='))
		after = mock.substring(mock.indexOf('=') + 1)
	} else if (/^.*\.\..*$/.test(mock)) {
		before = mock
	} else if (/^[\"].*[\"]$/.test(mock)) {
		after = mock
	}

	// 解析before
	if (/^.*\.\..*$/.test(before)) {
		lengthArray = before.split('..')
		lengthArray[0] ? _minLength = parseInt(lengthArray[0]) : null
		lengthArray[1] ? _maxLength = parseInt(lengthArray[1]) : null
	}

	// 解析after
	if (/^[\"].*[\"]$/.test(after)) {
		let array = after.split(',')
		if (array.length > 1) {
			_enum = array
		} else {
			_mock = array[0].trim()
		}
	} else {
		if (after) {
			_mock = ('@' + after).trim()
		}
	}

	if (_mock) {
		// 如果已有mock类型就不再设置
	} else if (validator.isInt(value)) {
		_mock = '@integer'
	} else if (validator.isFloat(value)) {
		_mock = '@float'
	} else if (validator.isBoolean(value)) {
		_mock = '@boolean'
	} else if (validator.isIP(value)) {
		_mock = '@ip'
	} else if (validator.isUUID(value)) {
		_mock = '@guid'
	} else if (/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/.test(value)) {
		_mock = '@datetime'
	} else if (/^\d{4}\-\d{2}\-\d{2}$/.test(value)) {
		_mock = '@date'
	} else if (/^\d{2}\:\d{2}\:\d{2}$/.test(value)) {
		_mock = '@time'
	} else if (validator.isHexColor(value)) {
		_mock = '@hex'
	} else if (validator.isEmail(value)) {
		_mock = '@email'
	} else if (validator.isURL(value)) {
		_mock = '@url'
	} else {
		_mock = '@string'
	}

	return {
		_mock,
		_minLength,
		_maxLength,
		_enum
	}
}

function commentParser (comments, line) {
	// 默认值
	let comment = ''
	if (comments instanceof Array) {
		if (comments.length > 0) {
			comment = comments[0]
		}
	} else {
		comment = comments
	}

	// 寻找同行comment
	if (typeof line !== 'undefined') {
		sameLineComments = comments.filter(comment => comment._position._start.line === line)
		comment = sameLineComments instanceof Array && sameLineComments.length > 0 ? sameLineComments[0]._value : ''
	}
	comment = comment ? comment.trim() : ''
	
	let { mock, rest: rest1 } = mockInfoParser(comment)
	let { required, rest: rest2 } = requiredParser(rest1)
	comment = rest2

	return {
		_required: required,
		_mock: mock,
		_comment: comment
	}
}

module.exports = {
	commentParser,
	mockStringParser,
	mockNumberParser
}
