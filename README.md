# invoice
发票开具和发票OCR查验工厂模式和入口，支持自定义扩展和添加其他第三方，发票开具由于税盘限制，只做了单个开票渠道开具，查验则以查取最终结果为准

出于保密性，只有部分代码
一个基于YII 
	工厂模式的开票逻辑，添加开票渠道不改动原有代码，解耦
	自动匹配不同租户不同配置库的登录db切换逻辑