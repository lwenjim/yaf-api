## 构建须知

#### 安装配置
* yaf PHP拓展
* orm laravel数据库模型


#### 运行配置
```code
[yaf]
yaf.environ = 'dev'
yaf.use_namespace = 1
yaf.use_spl_autoload = 1
```

#### 路由协议
* Rewrite
* Restful 
* Default

#### 基本设计
* 1.yaf + orm(eloquent) [yaf-参考](https://www.php.net/manual/zh/book.yaf.php) [eloquent-参考](https://laravel.com/docs/6.x/eloquent)
* 2.接口统一采取 restful 开发规范 [参考](http://www.ruanyifeng.com/blog/2018/10/restful-api-best-practices.html)
* 3.请求方式支持 GET，POST，PUT(HTTP HEADER 添加 X-HTTP-Method-Override:PUT)
* 4.接口返回的数据都经过 Transformer 格式化输出，因此每个 controller 都有对应的 model，service，transfer
* 5.由于yaf规范 controller 名称仅首字母大写，当多个单词构成一个controller 的时候 后面的单词必须小写，违背了类的驼峰命名
* 6.为了兼容路径中大小写命名的问题，声明 controller 文件名映射类名的map（controller::getControllerAlias）
* 7.在controller和service层都采取了AOP 设计 [参考](https://www.jianshu.com/p/9f0a98ce8a8f)
* 8.controller层aop主要解决 校验数据的合法性，数据校验采用的是 laraval-validation 验证模块[参考](https://laravel.com/docs/6.x/validation)
* 9.service层aop主要解决实体之间外键的关联，保持联动更新
* 10.采用league/fractal模型层到表现层转换 [参考](https://github.com/thephpleague/fractal)
