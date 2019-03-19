function mockParser (source) {
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

function mockDetailParser (source) {
	let _mock = null
	let _minLength = null
	let _maxLength = null
	let _enum = null

	// 拆分
	let before = ''
	let after = ''
	if (/^.*\=.*$/.test(source)) {
		before = source.substring(0, source.indexOf('='))
		after = source.substring(source.indexOf('=') + 1)
	} else if (/^.*\.\..*$/.test(source)) {
		before = source
	} else if (/^[\"].*[\"]$/.test(source)) {
		after = source
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

	return {
		_mock,
		_minLength,
		_maxLength,
		_enum
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
	
	let { mock, rest: rest1 } = mockParser(comment)
	let { required, rest: rest2 } = requiredParser(rest1)
	comment = rest2

	return {
		required,
		mock,
		comment
	}
}

module.exports = {
	mockParser,
	mockDetailParser,
	requiredParser,
	commentParser
}
