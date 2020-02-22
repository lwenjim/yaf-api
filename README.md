## 构建须知

## 此系统基于php yaf扩展框架构建

* yaf介绍:http://www.laruence.com/2011/05/12/2009.html
* yaf版本:请用3.0.7版本编译构建
* yaf地址:http://pecl.php.net/package/yaf/3.0.7
* yaf文档:http://php.net/manual/en/book.yaf.php
* yaf用户手册:http://www.laruence.com/manual/

## 开发环境php.ini yaf配置

```code
[yaf]
yaf.environ = 'dev'
yaf.use_namespace = 1
yaf.use_spl_autoload = 1
```

## 一些常用的文档

* [Particle\Validator](http://validator.particle-php.com/en/latest/rules/#included-validation-rules) 是一个小巧优雅的验证类库，提供了一个非常简洁的API

## 开发的一些原则和规范

 * 编写的代码请遵循 PSR-2 风格 [关于PSR](https://psr.phphub.org/)
 * 所有代码请设置为utf-8编码
 * 数据库所有编码请设置为utf8mb4
 * 在API层请调用SDK的Service相关方法访问数据
 * 在API层禁止直接调用SDK的Model相关方法


## 框架设计
* 1.yaf + orm(eloquent) [yaf-参考](https://www.php.net/manual/zh/book.yaf.php) [eloquent-参考](https://laravel.com/docs/6.x/eloquent)
* 2.接口统一采取 restful 合计规范 [参考](http://www.ruanyifeng.com/blog/2018/10/restful-api-best-practices.html)
* 3.请求方式支持 GET，POST，PUT(HTTP HEADER 添加 X-HTTP-Method-Override:PUT)
* 4.接口返回的数据都经过 Transformer 格式化输出，因此每个 controller 都有对应的 model，service，transfer
* 5.由于yaf规范 controller 名称仅首字母大写，当多个单词构成一个controller 的时候 后面的单词必须小写，违背了类的驼峰命名，
* 6.为了兼容路径中大小写命名的问题，声明 controller 文件名映射类名的map（controller::getControllerAlias）
* 7.在 controller 和 service 层 都采取了 AOP 设计 [参考](https://www.jianshu.com/p/9f0a98ce8a8f)
* 8.controller 层 aop 主要解决 校验数据的合法性，数据校验采用的是 laraval-validation 验证模块[参考](https://laravel.com/docs/6.x/validation)
* 9.service 层 aop 主要解决 实体之间外键的关联，保持联动更新
* 10.采用 league/fractal 模型层到表现层转换 [参考](https://github.com/thephpleague/fractal)

## 使用方法
