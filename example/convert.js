var _               = require('lodash');
const transformer   = require('../lib/index');
const path          = require('path');

const options= {
    dest: path.join(__dirname, './output'),
    src: path.join(__dirname, './input'),
    template: path.join(__dirname, '../template/'),

    debug: false,
    silent: false,
    verbose: false,
    simulate: false,
    parse: false, // only parse and return the data, no file creation
    colorize: true,
    markdown: true,

    marked: {
        gfm: true,
        tables: true,
        breaks: false,
        pedantic: false,
        sanitize: false,
        smartLists: false,
        smartypants: false
    }
}

transformer.createApidocSwagger(options);