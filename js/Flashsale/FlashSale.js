var FlashSale = function(playground, cart, products, timerContainer){
	this.playground = playground;
	this.cart = cart;
	this.products = products;
	this.timerContainer = timerContainer;
}

FlashSale.prototype = {
	registerSocket: function(socketUrl){
		this.websocket = new WebSocket(socketUrl);
		this.websocket.onopen = this.onopen.bind(this);
		this.websocket.onmessage = this.onmessage.bind(this);
		this.websocket.onerror = this.onerror.bind(this);
		this.websocket.onclose = this.onclose.bind(this);
	},
	sendMessageToSocket: function(msg){
		
		this.websocket.send(msg);
	},
	onopen: function(ev) {
		var msg = {
			"new": this._nickname
		}
		this.sendMessageToSocket(JSON.stringify(msg));
	},
	onmessage: function(ev) {
		var json = JSON.parse(ev.data);
		
		var	msg = json.message;
		if(json.lock){
			jQuery('#'+json.lock).draggable('disable');
		}
		else if(json.unlock){
			jQuery('#'+json.unlock).draggable('enable');
		}
		else if(json.delete){
			var element = document.getElementById(json.delete);
			if(element) this.playground.removeChild(element);
			this._notify(msg);
			return;
		}
		else if(json.timer){
			this.timeRemaining = parseInt(json.timer);
			this.startTimer();
		}
		if(!msg){
			return;
		}
		else if(json.new){
			this._notify(msg);
			return;
		}
		if(msg.ip == this.myIP){
			return;
		}
		var element = document.getElementById(msg.id);
		if(!element) return;
		element.style.left = msg.left;
		element.style.top = msg.top;
	},
	onerror: function(ev){
		this.redirectSafe();
	},
	onclose: function(ev){
		
	},
	redirectSafe: function(){
		this.warnBeforeClose(false);
		setLocation(this.homePage);
	},
	_myCart: [],
	startTimer: function(){
		var time = this.timeRemaining;
		var self = this;
		this.timerInterval = setInterval(function(){
			if(self.timeRemaining <= 0){
				clearInterval(self.timerInterval);
				self.saleEndCallback();
				return;
			}
			self.timerContainer.innerHTML = new Date(self.timeRemaining * 1000).toISOString().substr(11, 8);
			self.timeRemaining--;
		},1000);
	},
	saleEndCallback: function(){

	},
	draggableClass: "",
	_addListeners: function(){
		var self = this;
		jQuery('.'+self.draggableClass).draggable({
			drag: function(){
				self.sendDragInfoToSocket(this,self);
			},
			start: function(){
				self.lockProduct(this, self);
			},
			stop: function(){
				if(self._isColliding({
					dx: this.style.left.replace("px",""),
					dy: this.style.top.replace("px",""),
					width: this.style.width.replace("px",""),
					height: this.style.height.replace("px","")
				})){
					jQuery(self.cart).addClass('cart-hover');
					setTimeout(function(){
						jQuery(self.cart).removeClass('cart-hover');
					},200);
					self._addToCart(this);
				}
				else{
					self.unlockProduct(this, self);
				}
			},
			containment: "parent"
		});
	},
	lockProduct: function(elem, self){
		var msg = {
			"lock": elem.id
		}
		self.sendMessageToSocket(JSON.stringify(msg))
	},
	unlockProduct: function(elem, self){
		var msg = {
			"unlock": elem.id
		}
		self.sendMessageToSocket(JSON.stringify(msg))		
	},
	sendDragInfoToSocket: function(elem, self){
		var msg = {
			"message": {
				id: elem.id,
				left: elem.style.left,
				top: elem.style.top
			}
		};
		self.sendMessageToSocket(JSON.stringify(msg));
	},
	_loadProductsFromJSON: function(){
		var i;
		for(i = 0;i < this.products.length; i++){
			var div = document.createElement("img");
			div.style.position = "absolute";
			div.id = this.products[i].id;
			div.style.left = this.products[i].left;
			div.style.top = this.products[i].top;
			div.className = this.draggableClass;
			div.style.width = this.products[i].width;
			div.title = this.products[i].title;
			div.style.height = this.products[i].height;
			div.src = this.products[i].image_url;
			div.style.backgroundSize = "cover";
			div.style.opacity = this.products[i].opacity;
			this.playground.appendChild(div);
		}
	},
	_isColliding: function(obj1){
		var cart = this.cart;
		obj1.dx = parseInt(obj1.dx);
		obj1.dy = parseInt(obj1.dy);
		obj1.width = parseInt(obj1.width);
		obj1.height = parseInt(obj1.height);
		var obj2 = {
			dx: parseInt(cart.offsetLeft),
			dy: parseInt(cart.offsetTop),
			width: parseInt(cart.style.width.replace("px","")),
			height: parseInt(cart.style.height.replace("px",""))
		};
		
		var obj1_left_edge = obj1.dx;
		var obj2_left_edge = obj2.dx;
		var obj1_right_edge = obj1_left_edge+obj1.width;
		var obj2_right_edge = obj2_left_edge+obj2.width;
		var obj1_top_edge = obj1.dy;
		var obj2_top_edge = obj2.dy;
		var obj1_bottom_edge = obj1_top_edge+obj1.height;
		var obj2_bottom_edge = obj2_top_edge+obj2.height;

		var aLeftOfB = obj1_right_edge <= obj2_left_edge;
	    var aRightOfB = obj1_left_edge >= obj2_right_edge;
	    var aAboveB = obj1_bottom_edge <= obj2_top_edge;
	    var aBelowB = obj1_top_edge >= obj2_bottom_edge;

		if(!( aLeftOfB || aRightOfB || aAboveB || aBelowB )){
			return true;
		}
		return false;
	},
	_addToCart: function(elem){
		if(!elem){
			return;
		}
		this._myCart.push(elem.id);
		var msg = {
			"delete": elem.id,
			"title": elem.title,
			"player": this._nickname
		}
		this.sendMessageToSocket(JSON.stringify(msg));
	},
	notify: false,
	_notify: function(message){
		if(!this.notify) return;
		Notification.requestPermission().then(function(result) {
		  	var notification = new Notification(message);
		});
	},
	warnBeforeClose: function(warn){
		if(warn)	window.addEventListener("beforeunload", this.registerCloseBeforeListener);
		else window.removeEventListener("beforeunload", this.registerCloseBeforeListener, false);
	},
	registerCloseBeforeListener: function (e) {
		var confirmationMessage = 'It looks like you have been editing something. '
	                            + 'If you leave before saving, your changes will be lost.';

	    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
	    return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
	},
	start: function(){
		while(!this._nickname){
			this._nickname = prompt("Enter a nickname to begin the sale (visible to others)");
		}
		this._loadProductsFromJSON();
		this._addListeners();
	}


}