<?php
namespace Common\Lib;

class Constant{
	/**
	 * Exception
	 */
	// No Wechat Config Found.
	const EXCEPTION_NO_WECHAT_CONFIG_ERROR = 1;
	// Event Callback Function Not Exist
	const EXCEPTION_EVENT_CALLBACK_ERROR = 2;
	// Get Wechat Access Token Error
	const EXCEPTION_GET_ACCESS_TOKEN_ERROR = 3;
	// Get Wechat User Info Error
	const EXCEPTION_GET_USER_INFO_ERROR = 4;
	// Get Wechat Menu List Error
	const EXCEPTION_GET_MENU_LIST_ERROR = 5;
	// Save Wechat Menu List Error
	const EXCEPTION_SAVE_MENU_LIST_ERROR = 6;
	// Get Openid From Code Error
	const EXCEPTION_CODE_TO_OPENID_ERROR = 7;
	// Send wechat template msg error
	const EXCEPTION_SEND_WECHAT_TEMPLATE_MSG_ERROR = 8;

	/**
	 * Api 状态码
	 */
	const API_SUCCESS = 0;
	const API_FAILED = -1;
	// sign error
	const API_SIGN_ERROR = 10001;
	// Save auth log openid error
	const API_SAVE_AUTH_LOG_OPENID_ERROR = 10002;
	// Order submit param error
	const API_ORDER_SUBMIT_PARAM_ERROR = 10003;
	// Order Submit Error
	const API_ORDER_SUBMIT_ERROR = 10004;
	// Get order list param error
	const API_GET_ORDER_LIST_PARAM_ERROR = 10005;
	// Goods is not exists
	const API_GOODS_IS_NOT_EXIST = 10006;
	// Goods cate is not exists
	const API_GOODS_CATE_IS_NOT_EXIST = 10007;
	// Order is not exists
	const API_DELIVER_ORDER_INFO_PARAM_ERROR = 10008;
	// Order is not exists
	const API_DELIVER_ACCEPT_ORDER_PARAM_ERROR = 10009;
	// Deliver info error
	const API_DELIVER_INFO_ERROR = 10017;
	// Order allready accepted
	const API_DELIVER_ORDER_ALREADY_ACCEPT = 10010;
	// Order is canceled
	const API_DELIVER_ORDER_ALREADY_CANCELED = 10011;
	// Get order info order_id illegal
	const API_ORDER_INFO_PARAM_ERROR = 100012;
	// Order is not exists
	const API_ORDER_NOT_EXIST = 10013;
	// Order is not allow finished
	const API_ORDER_CAN_NOT_FINISH = 10014;
	// User address param error
	const API_USER_ADDRESS_PARAM_ERROR = 10015;
	// User address is not exists
	const API_USER_ADDRESS_NOT_EXIST = 10016;
	// Set default address error
	const API_USER_ADDRESS_SET_SEFAULT_ERROR = 10018;
	// Add address error
	const API_USER_ADDRESS_ADD_ERROR = 10019;
	// Save address error
	const API_USER_ADDRESS_SAVE_ERROR = 10020;
	// Delete address error
	const API_USER_ADDRESS_DELETE_ERROR = 10021;
	// Carousel param error
	const API_CAROUSEL_PARAM_ERROR = 10022;
	// Get ticket detail error
	const API_TICKET_DETAIL_PARAM_ERROR = 10023;
	// No ticket for goods
	const API_TICKET_NOT_EXIST = 10024;
	// Get user bucket info error
	const API_USER_GET_BUCKET_ERROR = 10025;
	// Get user info error
	const API_USER_ERROR = 10026;
	// GET user ticket error
	const API_GET_USER_TICKETS_PARAM_ERROR = 10027;
	// Order info error
	const API_CALLBACK_ERROR = 10028;
	// Add user wechat address error
	const API_ADD_USER_POINT_ERROR = 10029;
	// 水票过期时间
	const API_TICKET_OUT_TIME = 172800;

	/**
	 * GOODS表字段
	 */
	// 商品状态，在售
	const GOODS_STATUS_ONSALE = 0;
	// 商品状态，下架
	const GOODS_STATUS_DOWN = 1;
	// 商品状态，删除
	const GOODS_STATUS_DEL = 2;
	// 商品推荐状态，未推荐至首页
	const GOODS_IS_NOT_RECOMMENDED = 0;
	// 商品推荐状态，已推荐至首页
	const GOODS_IS_RECOMMENDED = 1;
	// 查询商品列表商品个数
	const GOODS_LIMIT=1000;
	// 商品允许添加水票
	const GOODS_TICKET_ALLOW = 1;
	// 商品允许添加水票
	const GOODS_TICKET_NOT_ALLOW = 0;
	/**
	 * GOODS_STRATEGY表字段
	 */
	// 水票
	const GOODS_STRATEGY_TYPE_TICKET = 1;
	// 套餐
	const GOODS_STRATEGY_TYPE_PACKAGE = 2;
	// 组合状态：0正常
	const GOODS_STRATEGY_TYPE_ONSALE = 0;
	// 组合状态：1禁用
	const GOODS_STRATEGY_TYPE_DOWN = 1;
	// 组合状态：2删除
	const GOODS_STRATEGY_TYPE_DEL = 2;

	/**
	 * GOODS_CATE表字段
	 */
	const GOODS_CATE_STATUS_ON=0;
	const GOODS_CATE_STATUS_DOWN=1;
	const GOODS_CATE_STATUS_DEL=2;
	

	/**
	 * USER_ADDRESS表字段
	 */
	// 微信用户收货地址状态，可用
	const USER_ADDRESS_STATUS_ONUSE = 0;
	// 微信用户收货地址状态，默认
	const USER_ADDRESS_STATUS_DEFAULT = 1;
	// 微信用户收货地址状态，删除
	const USER_ADDRESS_STATUS_DEL = 2;

	// b2c订单退桶单价
	const B2C_ORDER_BUCKET_PRICE = 50.00;
	// b2c订单类型:普通订单
	const B2C_ORDER_TYPE_NORMAL = 0;
	// b2c订单类型:退桶订单
	const B2C_ORDER_TYPE_BUCKET = 1;
	// b2c订单类型:组合订单
	const B2C_ORDER_TYPE_COMBINE = 2;

	// b2c订单类型组合商品提交，水票订单
	const B2C_ORDER_SUBMIT_TYPE_TICKET = 1;
	// b2c订单类型组合商品提交，套餐订单
	const B2C_ORDER_SUBMIT_TYPE_PACKAGE = 2;

	// B2C订单支付方式，线下支付
	const B2C_ORDER_PAY_TYPE_CASH = 0;
	// B2C订单支付方式，微信支付
	const B2C_ORDER_PAY_TYPE_WECHAT = 1;
	// B2C订单支付方式，水票支付
	const B2C_ORDER_PAY_TYPE_TICKET = 2;
    // B2C订单支付方式，水票支付和线下支付
    const B2C_ORDER_PAY_TYPE_TICKET_CASH = 3;
    // B2C订单支付方式，水票支付和微信支付
    const B2C_ORDER_PAY_TYPE_TICKET_WECHAT = 4;

	// B2C订单支付方式，所有支付方式
	const B2C_ORDER_PAY_TYPE_ALL = 100;

	// B2C订单状态，下单成功
	const B2C_ORDER_STATUS_CREATED = 0;
	// B2C订单状态，水站已经接单
	const B2C_ORDER_STATUS_STATION_ACCEPT = 1;
	// B2C订单状态，水工配送中
	const B2C_ORDER_STATUS_DELIVERING = 2;
	// B2C订单状态，已完成
	const B2C_ORDER_STATUS_FINISHED = 3;
	// B2C订单状态，已取消
	const B2C_ORDER_STATUS_CANCELED = 4;
	// B2C订单状态，已关闭
	const B2C_ORDER_STATUS_CLOSED = 5;
	// B2C订单状态，等待支付
	const B2C_ORDER_STATUS_WAITING = 6;
	// B2C订单状态，所有状态
	const B2C_ORDER_STATUS_ALL = 100;

	// B2C订单支付状态，未支付
	const B2C_ORDER_PAY_STATUS_NOPAY = 0;
	// B2C订单支付状态，支付成功
	const B2C_ORDER_PAY_STATUS_SUCCESS = 1;
	// B2C订单支付状态，支付失败
	const B2C_ORDER_PAY_STATUS_FAILED = 2;
	// B2C订单支付状态，退款中
	const B2C_ORDER_PAY_STATUS_BACKING = 3;
	// B2C订单支付状态，已经退款
	const B2C_ORDER_PAY_STATUS_BACKED = 4;
	// B2C订单支付状态，所有状态
	const B2C_ORDER_PAY_STATUS_ALL = 100;

	// B2C订单分派状态，未分派
	const B2C_ORDER_DELIVER_TYPE_NONE = 0;
	// B2C订单分派状态，已分派
	const B2C_ORDER_DELIVER_TYPE_ALLREADY = 1;
	// B2C订单分派状态，所有情况
	const B2C_ORDER_DELIVER_TYPE_BOTH = 100;

	// B2C订单详情类型普通商品
	const B2C_ORDER_DETAIL_GOODS_TYPE_GOODS = 0;
	// B2C订单详情类型水票商品
	const B2C_ORDER_DETAIL_GOODS_TYPE_TICKET = 1;

	// 水站水工状态，未启用
	const STATION_DELIVER_STATUS_CLOSE = 0;
	// 水站水工状态，已启用
	const STATION_DELIVER_STATUS_OPEN = 1;
	// 水站水工状态，已删除
	const STATION_DELIVER_STATUS_DEL = 2;

	// 水站老板状态，未启用
	const STATION_BOSS_STATUS_CLOSE = 0;
	// 水站老板状态，已启用
	const STATION_BOSS_STATUS_OPEN = 1;
	// 水站老板状态，已删除
	const STATION_BOSS_STATUS_DEL = 2;


	// 组合商品表商品状态： 正常0
	const B2C_STRATEGY_STATUS_ON = 0;
	// 组合商品表商品状态： 禁用1
	const B2C_STRATEGY_STATUS_DOWN = 1;
	// 组合商品表商品状态： 删除2
	const B2C_STRATEGY_STATUS_DEL = 2;


	/**
	 * CAROUSEL表字段
	 */
	// 轮播图类型
	const CAROUSEL_TYPE_B2C = 0;
	const CAROUSEL_TYPE_B2B = 1;
	const CAROUSEL_STATUS_ON = 0;
	const CAROUSEL_SHOW_LIMIT = 10;

	// 订单获得积分比例1:____
	const B2C_ORDER_INTEGRAL_PRO = 1;

	// 用户水票未支付
	const WECHAT_USER_TICKET_NO_PAY = 0;
	// 用户水票未使用
	const WECHAT_USER_TICKET_NORMAL = 1;
	// 用户水票已使用
	const WECHAT_USER_TICKET_USED = 2;
	// 用户水票购买锁定中
	const WECHAT_USER_TICKET_LOCKED = 3;
	// 用户水票使用锁定中
	const WECHAT_USER_TICKET_ORDER_LOCKED = 4;

    //水店状态，启用
    const STATION_STATUS_ONSALE = 0;
    // 水店状态，禁用
    const STATION_STATUS_DOWN = 1;
    // 水店状态，删除
    const STATION_STATUS_DEL = 2;


	// 新订单推送方式:水工抢单0,老板派单1
	const DELIVER_MODEL = 1;
	// 用户是否为老板
	const USER_TYPE_NORMAL = 0;
	const USER_TYPE_BOSS = 1;
}