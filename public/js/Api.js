!function(a){"use strict";function b(a,b){var c=(65535&a)+(65535&b),d=(a>>16)+(b>>16)+(c>>16);return d<<16|65535&c}function c(a,b){return a<<b|a>>>32-b}function d(a,d,e,f,g,h){return b(c(b(b(d,a),b(f,h)),g),e)}function e(a,b,c,e,f,g,h){return d(b&c|~b&e,a,b,f,g,h)}function f(a,b,c,e,f,g,h){return d(b&e|c&~e,a,b,f,g,h)}function g(a,b,c,e,f,g,h){return d(b^c^e,a,b,f,g,h)}function h(a,b,c,e,f,g,h){return d(c^(b|~e),a,b,f,g,h)}function i(a,c){a[c>>5]|=128<<c%32,a[(c+64>>>9<<4)+14]=c;var d,i,j,k,l,m=1732584193,n=-271733879,o=-1732584194,p=271733878;for(d=0;d<a.length;d+=16)i=m,j=n,k=o,l=p,m=e(m,n,o,p,a[d],7,-680876936),p=e(p,m,n,o,a[d+1],12,-389564586),o=e(o,p,m,n,a[d+2],17,606105819),n=e(n,o,p,m,a[d+3],22,-1044525330),m=e(m,n,o,p,a[d+4],7,-176418897),p=e(p,m,n,o,a[d+5],12,1200080426),o=e(o,p,m,n,a[d+6],17,-1473231341),n=e(n,o,p,m,a[d+7],22,-45705983),m=e(m,n,o,p,a[d+8],7,1770035416),p=e(p,m,n,o,a[d+9],12,-1958414417),o=e(o,p,m,n,a[d+10],17,-42063),n=e(n,o,p,m,a[d+11],22,-1990404162),m=e(m,n,o,p,a[d+12],7,1804603682),p=e(p,m,n,o,a[d+13],12,-40341101),o=e(o,p,m,n,a[d+14],17,-1502002290),n=e(n,o,p,m,a[d+15],22,1236535329),m=f(m,n,o,p,a[d+1],5,-165796510),p=f(p,m,n,o,a[d+6],9,-1069501632),o=f(o,p,m,n,a[d+11],14,643717713),n=f(n,o,p,m,a[d],20,-373897302),m=f(m,n,o,p,a[d+5],5,-701558691),p=f(p,m,n,o,a[d+10],9,38016083),o=f(o,p,m,n,a[d+15],14,-660478335),n=f(n,o,p,m,a[d+4],20,-405537848),m=f(m,n,o,p,a[d+9],5,568446438),p=f(p,m,n,o,a[d+14],9,-1019803690),o=f(o,p,m,n,a[d+3],14,-187363961),n=f(n,o,p,m,a[d+8],20,1163531501),m=f(m,n,o,p,a[d+13],5,-1444681467),p=f(p,m,n,o,a[d+2],9,-51403784),o=f(o,p,m,n,a[d+7],14,1735328473),n=f(n,o,p,m,a[d+12],20,-1926607734),m=g(m,n,o,p,a[d+5],4,-378558),p=g(p,m,n,o,a[d+8],11,-2022574463),o=g(o,p,m,n,a[d+11],16,1839030562),n=g(n,o,p,m,a[d+14],23,-35309556),m=g(m,n,o,p,a[d+1],4,-1530992060),p=g(p,m,n,o,a[d+4],11,1272893353),o=g(o,p,m,n,a[d+7],16,-155497632),n=g(n,o,p,m,a[d+10],23,-1094730640),m=g(m,n,o,p,a[d+13],4,681279174),p=g(p,m,n,o,a[d],11,-358537222),o=g(o,p,m,n,a[d+3],16,-722521979),n=g(n,o,p,m,a[d+6],23,76029189),m=g(m,n,o,p,a[d+9],4,-640364487),p=g(p,m,n,o,a[d+12],11,-421815835),o=g(o,p,m,n,a[d+15],16,530742520),n=g(n,o,p,m,a[d+2],23,-995338651),m=h(m,n,o,p,a[d],6,-198630844),p=h(p,m,n,o,a[d+7],10,1126891415),o=h(o,p,m,n,a[d+14],15,-1416354905),n=h(n,o,p,m,a[d+5],21,-57434055),m=h(m,n,o,p,a[d+12],6,1700485571),p=h(p,m,n,o,a[d+3],10,-1894986606),o=h(o,p,m,n,a[d+10],15,-1051523),n=h(n,o,p,m,a[d+1],21,-2054922799),m=h(m,n,o,p,a[d+8],6,1873313359),p=h(p,m,n,o,a[d+15],10,-30611744),o=h(o,p,m,n,a[d+6],15,-1560198380),n=h(n,o,p,m,a[d+13],21,1309151649),m=h(m,n,o,p,a[d+4],6,-145523070),p=h(p,m,n,o,a[d+11],10,-1120210379),o=h(o,p,m,n,a[d+2],15,718787259),n=h(n,o,p,m,a[d+9],21,-343485551),m=b(m,i),n=b(n,j),o=b(o,k),p=b(p,l);return[m,n,o,p]}function j(a){var b,c="";for(b=0;b<32*a.length;b+=8)c+=String.fromCharCode(a[b>>5]>>>b%32&255);return c}function k(a){var b,c=[];for(c[(a.length>>2)-1]=void 0,b=0;b<c.length;b+=1)c[b]=0;for(b=0;b<8*a.length;b+=8)c[b>>5]|=(255&a.charCodeAt(b/8))<<b%32;return c}function l(a){return j(i(k(a),8*a.length))}function m(a,b){var c,d,e=k(a),f=[],g=[];for(f[15]=g[15]=void 0,e.length>16&&(e=i(e,8*a.length)),c=0;16>c;c+=1)f[c]=909522486^e[c],g[c]=1549556828^e[c];return d=i(f.concat(k(b)),512+8*b.length),j(i(g.concat(d),640))}function n(a){var b,c,d="0123456789abcdef",e="";for(c=0;c<a.length;c+=1)b=a.charCodeAt(c),e+=d.charAt(b>>>4&15)+d.charAt(15&b);return e}function o(a){return unescape(encodeURIComponent(a))}function p(a){return l(o(a))}function q(a){return n(p(a))}function r(a,b){return m(o(a),o(b))}function s(a,b){return n(r(a,b))}function t(a,b,c){return b?c?r(b,a):s(b,a):c?p(a):q(a)}"function"==typeof define&&define.amd?define(function(){return t}):a.md5=t}(this);
var HOSTNAME = 'http://xiangshan.edshui.com';
var BUCKETPRICE = 50.00;
(function(){
	function Cache(){
		this._debug = true;
		this._cacheApis = [];
		this._apisCacheIds = {};
		this._cacheExpire = {};
		try{
			this._apisCacheIds = JSON.parse(localStorage['_apisCacheIds']);
			this._cacheExpire = JSON.parse(localStorage['_cacheExpire']);
		}catch(e){
			this._apisCacheIds = {};
			this._cacheExpire = {};
		}
		// if(this._debug){
		// 	console.log('Api cache ids: ');
		// 	console.log(this._apisCacheIds);
		// 	console.log('Cache id expires: ');
		// 	console.log(this._cacheExpire);
		// }
	}
	Cache.prototype.get = function(key){
		this.refreshCache();
		var cache = localStorage[key];
		if(cache == ''){
			return false;
		}else{
			try{
				return JSON.parse(cache);
			}catch(e){
				return false;
			}
		}
	}
	Cache.prototype.getCacheId = function(url, data){
		var cacheIdArr = [];
		for(k in data){
			if(k != 'timestamp' && k != 'sign'){
				cacheIdArr.push(k+'='+data[k]);
			}
		}
		return md5(url+'&'+cacheIdArr.join('&'));
	}
	Cache.prototype.deleteApiCache = function(api){
		if(typeof this._apisCacheIds[api] == 'object' && this._apisCacheIds[api].length != 0){
			for(var i=0; i<this._apisCacheIds[api].length; ++i){
				this._cacheExpire[this._apisCacheIds[api][i]] = 0;
			}
			this.refreshCache();
		}else{
			console.log(typeof this._apisCacheIds[api]);
		}	
	}
	Cache.prototype.wetherToCache = function(url){
		for(apiName in this._cacheApis){
			if(url == Api.getUrl(apiName)){
				return true;
			}
		}
		return false;
	}
	Cache.prototype.setCacheApiUrls = function(apis){
		this._cacheApis = apis;
		for(api in this._cacheApis){
			if(typeof this._apisCacheIds[api] == 'undefined'){
				this._apisCacheIds[api] = [];
			}
		}
	}
	Cache.prototype.refreshCache = function(){
		for(cacheId in this._cacheExpire){
			if(parseInt(new Date().getTime()) > this._cacheExpire[cacheId]){
				localStorage[cacheId] = '';
				this._cacheExpire[cacheId] = 0;
			}
		}
		// if(this._debug){
		// 	console.log('refreshCache');
		// 	console.log('Api cache ids: ');
		// 	console.log(this._apisCacheIds);
		// 	console.log('Cache id expires: ');
		// 	console.log(this._cacheExpire);
		// }
		// 1. 更新接口缓存ID
		localStorage['_apisCacheIds'] = JSON.stringify(this._apisCacheIds);
		// 2. 更新缓存有效期
		localStorage['_cacheExpire'] = JSON.stringify(this._cacheExpire);
	}
	Cache.prototype.cache = function(key, value, expire){
		this._cacheExpire[key] = parseInt(new Date().getTime()) + expire*1000;
		localStorage[key] = JSON.stringify(value);
		this.refreshCache();
	}
	Cache.prototype.set = function(url, cacheId, data){
		var apiName = Api.getApiNameFromUrls(url);
		// 1. 保存接口下的缓存ID
		var cacheIdExist = false;
		for(var i=0; i<this._apisCacheIds[apiName].length; ++i){
			if(this._apisCacheIds[apiName][i] == cacheId){
				cacheIdExist = true;
			}
		}
		if(!cacheIdExist){
			this._apisCacheIds[apiName].push(cacheId);
		}
		// 2. 保存缓存ID的有效时间
		var expire = parseInt(this._cacheApis[apiName])*1000;
		this._cacheExpire[cacheId] = parseInt(new Date().getTime()) + expire;
		// 3. 保存缓存数据
		localStorage[cacheId] = JSON.stringify(data);
		this.refreshCache();
	}
	window.Cache = new Cache();
})();
(function(){
	function Api(){
		this._debug = true;
		this._apiUrls = {};
		this._apiAuth = {};		
		this._urlGet = {};

		this._error = function(msg){
			alert(msg);
			throw new Error(msg);
		}
		this._get = function(key){
			return window.localStorage[key];
		}
		this._set = function(key, value){
			window.localStorage[key] = value;
		}
		this._del = function(key){
			this._set(key, '');
		}
		this._setApiAuth = function(apiAuth){
			this._set('_apiAuth', JSON.stringify(apiAuth));
		}
		this._getApiAuth = function(){
			try{
				return JSON.parse(this._get('_apiAuth'));
			}catch(e){
				return {};
			}
		}
		this._urlParse = function(){
			var urlSplit = window.location.href.split('#');
			if(urlSplit.length == 2){
				var apiParams = urlSplit.pop().split('&');
				for(var i=0; i<apiParams.length; ++i){
					var paramItem = apiParams[i].split('=');
					if(paramItem.length == 2){
						this._urlGet[paramItem[0]] = paramItem[1];
					}
				}
			}
				
			var apiAuth = this._getApiAuth();
			for(k in apiAuth){
				this._urlGet[k] = apiAuth[k];
			}
			if(typeof this._urlGet['config_id'] != 'undefined' && !isNaN(parseInt(this._urlGet['config_id']))){
				var configId = parseInt(this._urlGet['config_id']);
			}else{
				var configId = 0;
			}
			if(typeof this._urlGet['openid'] != 'undefined' && 0 != this._urlGet['openid'].length){
				var openid = this._urlGet['openid'];
			}else{
				var openid = '';
			}
			if(configId == 0 || 0 == openid.length){
				this._error('Api parameters is not validate');
			}else{
				this._apiAuth = {
					'config_id' : configId,
					'openid' : openid,
				};
				this._setApiAuth(this._apiAuth);
			}
		}
		this._urlParse();
		//this._set('_apiAuth', {"config_id":2,"openid":"oClftwpauoNVQwWxfDI53Nxo9WLk"});
	}
	Api.prototype.Ajax = function(url, method, data, callback){
		var cacheId = Cache.getCacheId(url, data),
			_this = this,
			xhr = null;
		try{
			var cache = Cache.get(cacheId);
		}catch(e){
			var cache = false;
		}
		if(cache){
			if(this._debug){
				console.log('Cache hit. [cache_id: '+cacheId+']');
			}
			callback(cache);
			return cacheId;
		}
		if(window.XMLHttpRequest){
			xhr = new XMLHttpRequest();
		}else{
			xhr = new ActiveXObject('Microsoft.XMLHttp');
		}
		xhr.open(method, url);
		if(method == 'post'){
			var dataArr = [];
			for(k in data){
				dataArr.push(k+'='+data[k]);
			}
			data = dataArr.join('&');
			xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		}
		if(this._debug){
			console.log('Ajax Info: '+JSON.stringify({url:url, method:method, data: data}));
		}
		xhr.send(data);
		xhr.onreadystatechange = function(){
			if(xhr.readyState == 4 && xhr.status == 200){
				try{
					var rtn = JSON.parse(xhr.responseText);
				}catch(e){
					alert('系统错误，请稍候再试！');
					return false;
				}
				if(_this._debug){
					console.log('Ajax Return: '+JSON.stringify(rtn));
				}
				if(Cache.wetherToCache(url)){
					Cache.set(url, cacheId, rtn);
				}
				callback(rtn);
				return cacheId;
			}
		}
	}
	Api.prototype.Debug = function(debug){
		this._debug = debug;
	}
	Api.prototype.GetSign = function(){
		var timestamp = Date.parse(new Date()), signStr='';
		signStr += 'config_id='+this._apiAuth['config_id'];
		signStr += '&openid='+this._apiAuth['openid'];
		signStr += '&timestamp='+timestamp;
		return sign = {
			'timestamp' : timestamp,
			'sign' : md5(signStr),
		};
	}
	Api.prototype.Post = function(url, data, callback){
		var sign = this.GetSign();
		data['config_id'] = this._apiAuth['config_id'];
		data['openid'] = this._apiAuth['openid'];
		data['sign'] = sign['sign'];
		data['timestamp'] = sign['timestamp'];
		return this.Ajax(url, 'post', data, callback);
	}
	Api.prototype.setApiUrls = function(data){
		this._apiUrls = data;
	}
	Api.prototype.getApiNameFromUrls = function(url){
		for(name in this._apiUrls){
			if(this._apiUrls[name] == url){
				return name;
			}
		}
		return false;
	}
	Api.prototype.getUrl = function(name){
		return this._apiUrls[name];
	}
	Api.prototype.U = function(file, param){
		var paramArr = [];
		for(k in param){
			paramArr.push(k+'='+param[k]);
		}
		return file+'#'+paramArr.join('&');
	}
	Api.prototype.Get = function(key){
		if(typeof key == 'string'){
			return (typeof this._urlGet[key] == 'undefined') ? false : this._urlGet[key];
		}else{
			return this._urlGet;
		}
	}
	Api.prototype.orderFieldEnumToChars = function(order){
        switch(order['pay_type']){
            case 0: order['pay_type'] = '线下支付'; break;
            case 1: order['pay_type'] = '微信支付'; break;
            case 2: order['pay_type'] = '水票支付'; break;
            case 3: order['pay_type'] = '水票支付和线下支付'; break;
            case 4: order['pay_type'] = '水票支付和微信支付'; break;
            default: order['pay_type'] = '未知';
        }
        switch(order['pay_status']){
            case 0: order['pay_status'] = '未支付'; break;
            case 1: order['pay_status'] = '支付成功'; break;
            case 2: order['pay_status'] = '支付失败'; break;
            case 3: order['pay_status'] = '退款中'; break;
            case 4: order['pay_status'] = '已退款'; break;
            default: order['pay_status'] = '未知';
        }
        switch(parseInt(order['order_status'])){
            case 0: order['order_status'] = '下单成功'; break;
            case 1: order['order_status'] = '已接单'; break;
            case 2: order['order_status'] = '配送中'; break;
            case 3: order['order_status'] = '已送达'; break;
            case 4: order['order_status'] = '已取消'; break;
            case 5: order['order_status'] = '已关闭'; break;
            case 6: order['order_status'] = '待支付'; break;
            default: order['order_status'] = '未知';
        }
        Date.prototype.Format = function (fmt) { //author: meizz 
            var o = {
                "M+": this.getMonth() + 1, //月份 
                "d+": this.getDate(), //日 
                "h+": this.getHours(), //小时 
                "m+": this.getMinutes(), //分 
                "s+": this.getSeconds(), //秒 
                "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
                "S": this.getMilliseconds() //毫秒 
            };
            if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
            for (var k in o)
            if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
            return fmt;
        }
        order['create_time_ymd'] = new Date(parseInt(order['create_time'])*1000).Format('yyyy-MM-dd hh:mm:ss');
        order['create_time'] = new Date(parseInt(order['create_time'])*1000).Format('MM/dd hh:mm');
        order['deliver_accept_time'] = new Date(parseInt(order['deliver_accept_time'])*1000).Format('yyyy-MM-dd hh:mm:ss');
        return order;
    }
	window.Api = new Api();
})();
(function(){
	window.Api.Debug(true);
	window.Api.setApiUrls({
		'demo' : HOSTNAME+'/index.php?g=Apis&m=Auth&a=demo',
		'getRecommendGoods' : HOSTNAME+'/index.php?g=Apis&m=Goods&a=getRecommendGoods',
		'getGoodsDetail' : HOSTNAME+'/index.php?g=Apis&m=Goods&a=getGoodsDetail',
		'getGoodsList' : HOSTNAME+'/index.php?g=Apis&m=Goods&a=getGoodsList',
		'orderSubmit' : HOSTNAME+'/index.php?g=Apis&m=Order&a=orderSubmit',
		'combineOrderSubmit' : HOSTNAME+'/index.php?g=Apis&m=Order&a=combineOrderSubmit',
		'bindDeliver' : HOSTNAME+'/index.php?g=Apis&m=Deliver&a=bindDeliver',
		'deliverGetOrderList' : HOSTNAME+'/index.php?g=Apis&m=Deliver&a=orderList',
		'deliverGetOrderInfo' : HOSTNAME+'/index.php?g=Apis&m=Deliver&a=getOrderInfo',
		'deliverAcceptOrder' : HOSTNAME+'/index.php?g=Apis&m=Deliver&a=acceptOrder',
		'deliverFinishOrder' : HOSTNAME+'/index.php?g=Apis&m=Deliver&a=finishOrder',
		'bossGetOrderList' : HOSTNAME+'/index.php?g=Apis&m=Deliver&a=bossGetOrderList',
		'getCarousel' : HOSTNAME+'/index.php?g=Apis&m=Carousel&a=getCarousel',
		'getUserAddressList' : HOSTNAME+'/index.php?g=Apis&m=User&a=getUserAddressList',
		'setDefaultAddress' : HOSTNAME+'/index.php?g=Apis&m=User&a=setDefaultAddress',
		'deleteAddress' : HOSTNAME+'/index.php?g=Apis&m=User&a=deleteAddress',
		'getUserAddressInfo' : HOSTNAME+'/index.php?g=Apis&m=User&a=getUserAddressInfo',
		'getUserTickets' : HOSTNAME+'/index.php?g=Apis&m=User&a=getUserTickets',
		'getUserBucket' : HOSTNAME+'/index.php?g=Apis&m=User&a=getUserBucket',
		'saveAddress' : HOSTNAME+'/index.php?g=Apis&m=User&a=saveAddress',
		'addAddress' : HOSTNAME+'/index.php?g=Apis&m=User&a=addAddress',
		'getOrderList' : HOSTNAME+'/index.php?g=Apis&m=Order&a=getOrderList',
		'getOrderDetail' : HOSTNAME+'/index.php?g=Apis&m=Order&a=getOrderDetail',
		'getDefaultAddress' : HOSTNAME+'/index.php?g=Apis&m=User&a=getDefaultAddress',
		'getCombineGoodsList' : HOSTNAME+'/index.php?g=Apis&m=Goods&a=getCombineGoodsList',
		'getTicketGoodsList' : HOSTNAME+'/index.php?g=Apis&m=Goods&a=getTicketGoodsList',
		'getTicketGoodsDetail' : HOSTNAME+'/index.php?g=Apis&m=Goods&a=getTicketGoodsDetail',
		'getUserInfo' : HOSTNAME+'/index.php?g=Apis&m=User&a=getUserInfo',
		'getWechatPayJsParameters' : HOSTNAME+'/index.php?g=Apis&m=Wechat&a=getWechatPayJsParameters',
		'getOrderAvalibleTicketList' : HOSTNAME+'/index.php?g=Apis&m=Wechat&a=getOrderAvalibleTicketList',
		'saveUserGps' : HOSTNAME+'/index.php?g=Apis&m=User&a=saveUserGps',
		'bossList' : HOSTNAME+'/index.php?g=Apis&m=Boss&a=bossList',
		'bossBefore' : HOSTNAME+'/index.php?g=Apis&m=Boss&a=bossBefore',
		'bindBoss' :HOSTNAME+'/index.php?g=Apis&m=Boss&a=bindBoss',
        'bossDeliver' :HOSTNAME+'/index.php?g=Apis&m=Boss&a=bossDeliver',
        'bossDeliverSubmit' :HOSTNAME+'/index.php?g=Apis&m=Boss&a=bossDeliverSubmit',
		'sendCode' :HOSTNAME+'/index.php?g=Apis&m=Deliver&a=sendCode',
        'distributionList' :HOSTNAME+'/index.php?g=Apis&m=Distribution&a=distributionList',
        'getBossOrderDetail' :HOSTNAME+'/index.php?g=Apis&m=Boss&a=getBossOrderDetail',
        'getUserStationInfo' : HOSTNAME+'/index.php?g=Apis&m=User&a=getUserStationInfo'
	});
	window.Cache.setCacheApiUrls({
		'getRecommendGoods' : 60,
		'getGoodsDetail' : 60,
		'getGoodsList' : 60,
		'deliverGetOrderInfo' : 60,
		'getCarousel' : 60,
		'getUserAddressList' : 60,
		'getUserAddressInfo' : 60,
		'getOrderList' : 60,
		'getOrderDetail' : 60,
		'getDefaultAddress' : 60,
		'getCombineGoodsList' : 60,
		'getTicketGoodsList' : 60,
		'getTicketGoodsDetail' : 60,
		'deliverGetOrderList' : 60,
		'getUserTickets' : 60,
		'getUserInfo' : 60,
		'getOrderAvalibleTicketList': 60,
	});
})();