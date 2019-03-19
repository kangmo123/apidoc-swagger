/**
 * @api {get} /user/name 获取用户名称
 * @apiName GetUser
 * @apiGroup User
 *
 * @apiParam (path) {String} id Users unique ID
 * @apiParam {String} [firstname]  Firstname of the User.
 * @apiParam {String} lastname     Mandatory Lastname.
 * @apiParam {String} country="DE" Mandatory with default value "DE".
 * @apiParam {Number} [age=18]     Age with default 18.
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *     "name1": "",        // 名字，无默认值
 *     "name2": "Messi",   // 名字，有默认值
 *     "name3": "Messi",   // [] 名字，可选
 *     "name4": "Messi",   // {..20} 名字，最多20个字符
 *     "name5": "Messi",   // {10..20} 名字，10到20个字符
 *     "name6": "Messi",   // {name} 名字，并设置了名字mock数据类型
 *     "name7": "Messi",   // {"Lionel Messi"} 名字，并设置了固定值
 *     "name8": "Messi",   // {..20="Lionel Messi"} 名字，组合mock
 *     "name9": "Messi",   // {"Lionel Messi","Cristiano Ronaldo"} 名字，并设置了枚举值
 *     "name10": "Messi",  // {string} [] 名字，可选，并设置了string类型mock数据类型
 *     "name11": "Messi",  // {"Lionel Messi"} [] 名字，可选，并设置了固定值
 *     "array1":  // 多层嵌套数组
 *     [
 *         {
 *             "obj": {            // [] 可选的对象
 *                 "key": "value"  // key的值
 *             },
 *             "array2": [         // 必须的数组
 *                 "value"         // 值
 *             ]
 *         }
 *     ],
 *     "stringObj": {   // 字符串类型数据集合
 *         "integer": "101",       // 整数字符串
 *         "float": "434.34",      // 小数字符串
 *         "boolean": "false",     // 布尔字符串
 *         "ip": "192.168.0.1",    // IP地址
 *         "url": "http://www.qq.com/index.html",           // URL地址
 *         "uuid": "9b5f3289-8061-4352-980f-188c4218df67",  // UUID
 *         "datetime": "2019-03-18 14:15:01",               // 日期时间
 *         "imageData": "data:image/jpeg;base64,SGVsbG8sIFdvcmxkIQ%3D%3D",  // 图片数据
 *         "hex": "#FF00FF",                // hex颜色
 *         "email": "email@tencent.com",    // 电子邮件
 *         "string": "The String",          // 任意字符串
 *     },
 *     "numberObj": {                  // 数字类型数据集合
 *         "positiveInteger": 50,      // 正整数
 *         "negativeInteger": -50,     // 负整数
 *         "positiveFloat": 4433.555,  // 正小数
 *         "negativeFloat": -8.65,     // 负小数
 *     },
 *     "booleanObj": {        // 布尔类型数据集合
 *         "value1": true,    // 真
 *         "value2": false    // 假
 *     }
 * }
 */
