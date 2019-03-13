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
 * @apiSuccessExample {string} Success-Response:
 * HTTP/1.1 200 OK
 * asdfdf // ffff
 */
