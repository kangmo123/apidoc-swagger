异常定义

该目录下的文件都是用来定义系统可能会扔出的异常，建议使用异常来表示系统执行出错，请按照分类建立文件夹归类异常。

API目录下定义的异常都是为了编写RESTful接口方便准备的，包括了常用的HTTP错误情况，如果要自定义业务错误请选择对应的HTTP异常添加业务Code。
HTTP 400 - 499 表示客户端错误，能明确指定的异常就使用对应的异常，不能明确指定的统一用HTTP 400 BadRequest表示。
HTTP 500 - 599 表示服务器错误，能明确指定的异常就使用对应的异常，不能明确指定的统一用HTTP 500 InternalServerError表示。
扔出异常后系统会自动按照预先约定的API错误规范进行输出。

```
if ($somethingIsWrong) {
    throw new BadRequest();                                       //默认code和message
    throw new Unauthorized(Unauthorized::BIZ_CODE_DEFAULT);       //更改业务错误码, 使用默认message
    throw new Forbidden(Forbidden::BIZ_CODE_DEFAULT, "请求出错");  //更改业务错误码和message
}
```

使用异常的好处，请参考[此链接](https://docs.oracle.com/javase/tutorial/essential/exceptions/advantages.html)
PHP如何使用异常，请参考[此链接](https://www.w3schools.com/php/php_exception.asp)