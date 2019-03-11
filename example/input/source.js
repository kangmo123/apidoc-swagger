/**
 * @api {post} /user/:id Request User information
 * @apiName GetUser
 * @apiGroup User
 *
 * @apiParam (path) {String} id The id
 * @apiParam {String} [firstname]  Firstname of the User.
 * @apiParam {String} lastname     Mandatory Lastname.
 * @apiParam {String} country="DE" Mandatory with default value "DE".
 * @apiParam {Number} [age=18]     Age with default 18.
 *
 * @apiSuccessExample {json} Success-Response:
 * HTTP/1.1 200 OK
 * {
 *     "firstname": "John", // [] Firstname of the User.
 *     "lastname": "Doe", // Mandatory Lastname.
 *     "tttt":  // [] 数组
 *     [
 *        {
 *           "bbbb": 123,
 *           "yyyy": "434" // yyyy的注释
 *        } //
 *     ] //
 * }
 */
