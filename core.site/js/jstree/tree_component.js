/*
 * jsTree 0.8.1
 *
 * Copyright (c) 2008 Ivan Bozhanov (vakata.com)
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Date: 2008-10-09
 *
 */
function tree_component () {
	return {
		settings : {
			data	: {
				type	: "predefined",	// ENUM [json, xml_flat, xml_nested, predefined]
				async	: false,		// BOOL - async loading onopen
				url		: false,		// FALSE or STRING - url to document to be used (async or not)
				json	: false			// FALSE or OBJECT if type is JSON and async is false - the tree dump as json
			},
			dflt		: false,		// FALSE or STRING
			languages	: [],			// ARRAY of string values (which will be used as CSS classes - si they must be valid)
			path		: false,		// FALSE or STRING (if false - will be autodetected)
			images_path : false,
			hide_buttons: [],
			cookies		: false,		// FALSE or OBJECT (prefix, opts - from jqCookie - expires, path, domain, secure)
			ui		: {
				dots		: true,		// BOOL - dots or no dots
				rtl			: false,	// BOOL - is the tree right-to-left
				animation	: 0,		// INT - duration of open/close animations in miliseconds
				hover_mode	: true		// SHOULD get_* functions chage focus or change hovered item
			},
			rules	: {
				multiple	: false,	// FALSE | CTRL | ON - multiple selection off/ with or without holding Ctrl
				metadata	: false,	// FALSE or STRING - attribute name (use metadata plugin)
				type_attr	: "rel",	// STRING attribute name (where is the type stored if no metadata)
				multitree	: false,	// BOOL - is drag n drop between trees allowed
				createat	: "bottom",	// STRING (top or bottom) new nodes get inserted at top or bottom
				use_inline	: false,	// CHECK FOR INLINE RULES - REQUIRES METADATA
				clickable	: "all",	// which node types can the user select | default - all
				renameable	: "all",	// which node types can the user select | default - all
				deletable	: "all",	// which node types can the user delete | default - all
				creatable	: "all",	// which node types can the user create in | default - all
				draggable	: "none",	// which node types can the user move | default - none | "all"
				dragrules	: "none"	// what move operations between nodes are allowed | default - none | "all"
			},
			lang : {
				new_node	: "New folder",
				loading		: "Loading ..."
			},
			callback	: {				// various callbacks to attach custom logic to
				// before focus  - should return true | false
				beforechange: function(NODE,TREE_OBJ) { return true },
				// before move   - should return true | false
				beforemove  : function(NODE,REF_NODE,TYPE,TREE_OBJ) { return true },
				// before create - should return true | false
				beforecreate: function(NODE,REF_NODE,TYPE,TREE_OBJ) { return true },
				// before rename - should return true | false
				beforerename: function(NODE,LANG,TREE_OBJ) { return true },
				// before delete - should return true | false
				beforedelete: function(NODE,TREE_OBJ) { return true },

				onchange	: function(NODE,TREE_OBJ) { },					// focus changed
				onrename	: function(NODE,LANG,TREE_OBJ) { },				// node renamed ISNEW - TRUE|FALSE, current language
				onmove		: function(NODE,REF_NODE,TYPE,TREE_OBJ) { },	// move completed (TYPE is BELOW|ABOVE|INSIDE)
				oncopy		: function(NODE,REF_NODE,TYPE,TREE_OBJ) { },	// copy completed (TYPE is BELOW|ABOVE|INSIDE)
				oncreate	: function(NODE,REF_NODE,TYPE,TREE_OBJ) { },	// node created, parent node (TYPE is insertAt)
				ondelete	: function(NODE, TREE_OBJ) { },					// node deleted
				onopen		: function(NODE, TREE_OBJ) { },					// node opened
				onclose		: function(NODE, TREE_OBJ) { },					// node closed
				error		: function(TEXT, TREE_OBJ) { },					// error occured
				onclk		: function(NODE, TREE_OBJ) {},
				// double click on node - defaults to open/close & select
				ondblclk	: function(NODE, TREE_OBJ) { TREE_OBJ.toggle_branch.call(TREE_OBJ, NODE); TREE_OBJ.select_branch.call(TREE_OBJ, NODE); },
				// right click - to prevent use: EV.preventDefault(); EV.stopPropagation(); return false
				onrgtclk	: function(NODE, TREE_OBJ, EV) { },
				onload		: function(TREE_OBJ) { }
			}
		},
		// INITIALIZATION
		init : function(elem, opts) {
			var _this = this;
			this.container		= $(elem);

			// MERGE OPTIONS WITH DEFAULTS
			if(opts && opts.cookies) {
				this.settings.cookies = $.extend({},this.settings.cookies,opts.cookies);
				delete opts.cookies;
				if(!this.settings.cookies.opts) this.settings.cookies.opts = {};
			}
			if(opts && opts.callback) {
				this.settings.callback = $.extend({},this.settings.callback,opts.callback);
				delete opts.callback;
			}
			if(opts && opts.data) {
				this.settings.data = $.extend({},this.settings.data,opts.data);
				delete opts.data;
			}
			if(opts && opts.ui) {
				this.settings.ui = $.extend({},this.settings.ui,opts.ui);
				delete opts.ui;
			}
			if(opts && opts.rules) {
				this.settings.rules = $.extend({},this.settings.rules,opts.rules);
				delete opts.rules;
			}
			if(opts && opts.lang) {
				this.settings.lang = $.extend({},this.settings.lang,opts.lang);
				delete opts.lang;
			}
			this.settings		= $.extend({},this.settings,opts);

			// PATH TO IMAGES AND XSL
			if(this.settings.path == false) {
				this.path = "";
				$("script").each( function () {
					if(this.src.toString().match(/tree_component.js$/)) {
						_this.path = this.src.toString().replace("tree_component.js", "");
					}
				});
			}
			else this.path = this.settings.path;

			this.images_path = this.settings.images_path;

			// DEAL WITH LANGUAGE VERSIONS
			this.current_lang	= this.settings.languages && this.settings.languages.length ? this.settings.languages[0] : false;
			if(this.settings.languages && this.settings.languages.length) {
				this.sn = get_sheet_num("tree_component.css");
				var st = false;
				var id = this.container.attr("id") ? "#" + this.container.attr("id") : ".tree";
				for(var ln = 0; ln < this.settings.languages.length; ln++) {
					st = add_css(id + " ." + this.settings.languages[ln], this.sn);
					if(st !== false) {
						if(this.settings.languages[ln] == this.current_lang)	st.style.display = "inline";
						else													st.style.display = "none";
					}
				}
			}

			this.container.addClass("tree").css({ position: "relative" });
			if(this.settings.ui.rtl) this.container.addClass("rtl");
			if(this.settings.rules.multiple) this.selected_arr = [];
			this.offset = this.container.offset();
			this.container.css({ position : "" });
			if(this.settings.ui.dots == false) this.container.addClass("no_dots");

			this.hovered = false;
			this.locked = false;

			str = '';
			str += '<span class="js-tree-buttons">';
			str += '<img class="tree-edit" src="'+ this.images_path +'edit.png" ';
			str += '/>';

			str += '<img class="tree-child-add" src="'+ this.images_path +'addChild.png" width="15" height="13" ';
			str += '/>';

			str += '<img class="tree-brother-add" src="'+ this.images_path +'add.png" width="15" height="13" ';
			str += '/>';

			str += '<img class="tree-delete" src="'+ this.images_path +'del.png" ';
			str += '/>';
			str += '</span>';
            this.span_buttons = str;

			// CREATE DUMMY FOR MOVING
			if(this.settings.rules.draggable != "none" && this.settings.rules.dragrules != "none") {
				var _this = this;
				$("<img>")
					.attr({
						id		: "marker",
						src	: _this.images_path + "marker.gif"
					})
					.css({
						height		: "5px",
						width		: "40px",
						display		: "block",
						position	: "absolute",
						left		: "30px",
						top			: "30px",
						zIndex		: "100"
					}).hide().appendTo("body");
			}
			this.refresh();
			this.attachEvents();
		},
		// REPAINT TREE
		refresh : function (obj) {
			if(this.locked) return this.error("LOCKED");
			var _this = this;
			// SAVE SELECTED
			this.settings.dflt = (this.selected) ? "#" + this.selected.attr("id") : this.settings.dflt;
			if(this.settings.cookies) {
				var str = $.cookie(this.settings.cookies.prefix + '_selected');
				if(str) this.settings.dflt = "#" + str;
			}

			if(obj && this.settings.data.async) {
				this.opened = Array();
				obj = this.get_node(obj);
				obj.find("li.open").each(function (i) { _this.opened.push(this.id); });
				this.close_branch(obj, true);
				obj.children("ul:eq(0)").html("");
				return this.open_branch(obj, true, function () { _this.reselect.apply(_this); });
			}

			this.opened = Array();
			if(this.settings.cookies) {
				var str = $.cookie(this.settings.cookies.prefix + '_open');
				if(str && str.length)	str = str.split(",");
				else					str = [];
				if(str.length) this.opened = str;
			}
			else {
				this.container.find("li.open").each(function (i) { _this.opened.push(this.id); });
			}

			if(this.settings.data.type == "xml_flat" || this.settings.data.type == "xml_nested") {
				this.scrtop = this.container.get(0).scrollTop;
				var xsl = (this.settings.data.type == "xml_flat") ? "flat.xsl" : "nested.xsl";
				this.container.getTransform(this.path + xsl, this.settings.data.url, { callback: function () { _this.reselect.apply(_this); } });
				return;
			}
			else if(this.settings.data.type == "json") {
				if(this.settings.data.json) {
					chtml = '';
					for (var i = 0; i < this.settings.data.json.length; i++)
					{
						chtml += this.parseJSON(this.settings.data.json[i]);
					}
					this.container.html("<ul>" + chtml + "</ul>");
					this.container.find("li:last-child").addClass("last").end().find("li:has(ul)").not(".open").addClass("closed");
					this.container.find("li").not(".open").not(".closed").addClass("leaf");
					this.reselect();
				}
				else {
					var _this = this;
					$.getJSON(this.settings.data.url, { id : 0 }, function (data) {
						var str = "";
						if(data.length) {
							for(var i = 0; i < data.length; i++) {
								str += _this.parseJSON(data[i]);
							}
						} else str = _this.parseJSON(data);
						_this.container.html("<ul>" + str + "</ul>");
						_this.container.find("li:last-child").addClass("last").end().find("li:has(ul)").not(".open").addClass("closed");
						_this.container.find("li").not(".open").not(".closed").addClass("leaf");
						_this.reselect.apply(_this);
					});
				}
			}
			else {
				this.container.find("li:last-child").addClass("last").end().find("li:has(ul)").not(".open").addClass("closed");
				this.container.find("li").not(".open").not(".closed").addClass("leaf");
				this.reselect();
			}
		},
		// CONVERT JSON TO HTML
		parseJSON : function (data) {
			var str = "";
			str += "<li ";
			var cls = false;
			var dt;
			for(i in data.attributes) {
			    //console.log(i);
			    
				if(i == "class") {
					str += " class='" + data.attributes[i] + " ";
					if(data.state == "closed" || data.state == "open") str += " " + data.state + " ";
					str += "' ";
					cls = true;
				}
				else str += " " + i + "='" + data.attributes[i] + "' ";
				
				if (i == "data" )
				{
				    dt = eval( "("+data.attributes[i]+")") ;
				    
				    str += " rel='" + dt["path"] + "' ";
				}
			}
			if(!cls && (data.state == "closed" || data.state == "open")) str += " class='" + data.state + "' ";
			str += ">";
			if(this.settings.languages.length) {
				for(var i = 0; i < this.settings.languages.length; i++) {
					str += "<a href='#' class='" + this.settings.languages[i] + "' ";
					if(data.icons && data.icons[this.settings.languages[i]])
						str += " style='background-image:url(\"" + data.icons[this.settings.languages[i]] + "\");' ";
					str += ">" + data.data[this.settings.languages[i]] + "</a>";
				}
			} else {
				str += "<a href='#' ";
				if (data.icon)
				{
					str += 'style="background-image:url('+this.images_path+data.icon+')"'
				}
				str += ">" + data.data;
                
                str += '<span class="js-tree-buttons">';
                
                str += '<img title="�������������" class="tree-edit" src="'+ this.images_path +'edit.png" ';
				if (!data.custom_buttons)
				{
					if (this.settings['hide_buttons']['edit'] === true || ( this.settings['hide_buttons']['edit'] && this.settings['hide_buttons']['edit'][data.level] === true))
						str += 'hidden="hidden"';
				}
				else if (data.hide_buttons && data.hide_buttons.edit) str += 'hidden="hidden"';
				str += '/>';
				
				str += '<img title="�������� �����" class="tree-brother-add" src="'+ this.images_path +'add.png" width="15" height="13" ';
				if (!data.custom_buttons)
				{
					if (this.settings['hide_buttons']['addBrother'] === true || (this.settings['hide_buttons']['addBrother'] && this.settings['hide_buttons']['addBrother'][data.level] === true))
						str += 'hidden="hidden"';
				}
				else if (data.hide_buttons && data.hide_buttons.addBrother) str += 'hidden="hidden"';
				str += '/>';

				str += '<img title="�������� ������" class="tree-child-add" src="'+ this.images_path +'addChild.png" width="15" height="13" ';
				if (!data.custom_buttons)
				{
					if (this.settings['hide_buttons']['addChild'] === true || ( this.settings['hide_buttons']['addChild'] && this.settings['hide_buttons']['addChild'][data.level] === true))
						str += 'hidden="hidden"';
				}
				else if (data.hide_buttons && data.hide_buttons.addChild) str += 'hidden="hidden"';

				str += '/>';

				str += '<img title="�������" class="tree-delete" src="'+ this.images_path +'del.png" ';
				if (!data.custom_buttons)
				{
					if (this.settings['hide_buttons']['del'] === true || (this.settings['hide_buttons']['del'] && this.settings['hide_buttons']['del'][data.level] === true))
						str += 'hidden="hidden"';
				}
				else if (data.hide_buttons && data.hide_buttons.del) str += 'hidden="hidden"';
				str += '/>';

				str += '</span>';
				
				str += '</a>';

			}
			if(data.children && data.children.length) {
				str += '<ul>';
				for(var i = 0; i < data.children.length; i++) {
					str += this.parseJSON(data.children[i]);
				}
				str += '</ul>';
			}
			str += "</li>";
			return str;
		},
		// getJSON from HTML
		getJSON : function (nod) {
			if(!nod || $(nod).size() == 0)	nod = this.container.find("li:eq(0)");
			else							nod = $(nod);

			var _this = this;
			var obj = { attributes : {}, data : false, icons : false };
			nod_d = nod.get(0);
			for(i in nod_d.attributes) {
				obj.attributes[nod_d.attributes[i].name] = nod_d.attributes[i].value;
			}
			var a = nod.children("a");
			if(a.size() > 1) {
				obj.data = [];
				obj.icons = [];
				a.each(function () {
					obj.data.push(this.innerHTML);
					obj.icons.push(this.style.backgroundImage.replace("url(","").replace(")",""));
				});
			}
			else {
				obj.data = a.text();
				obj.icons = a.css("backgroundImage");
			}
			if(nod.children("ul").size() > 0) {
				obj.children = [];
				nod.children("ul").children("li").each(function () {
					obj.children.push(_this.getJSON(this));
				});
			}
			return obj;
		},
		// ALL EVENTS
		attachEvents : function () {
			var _this = this;

			var tmp = this.container.find("li.closed:eq(0)");
			if(tmp.size() == 0) tmp = this.container.find("li.leaf:eq(0)");
			this.li_height = tmp.height();
			if(!this.li_height) this.li_height = 18;

			this.container
				.bind("click", function (event) {
					event.stopPropagation();
					return true;
				})
				.listen("click", "li", function(event) { // WHEN CLICK IS ON THE ARROW
					_this.toggle_branch.apply(_this, [event.target]);
					event.stopPropagation();
				})
				.listen("click", "a", function (event) { // WHEN CLICK IS ON THE TEXT OR ICON
					if(_this.locked) {
						event.preventDefault();
						event.target.blur();
						return _this.error("LOCKED");
					}
					_this.select_branch.apply(_this, [event.target, event.ctrlKey || _this.settings.rules.multiple == "on"]);

					_this.settings.callback.onclk.call(null, _this.get_node(event.target).get(0), _this);

					if(_this.inp) { _this.inp.blur(); }
					event.preventDefault();
					event.target.blur();
					return false;
				})
				.listen("click", "img.tree-edit", function (event) {
					if(_this.locked) {
						event.preventDefault();
						return _this.error("LOCKED");
					}
					_this.selected = _this.get_node(event.target);
					_this.rename();

					event.preventDefault();
					return false;
				})
				.listen("click", "img.tree-child-add", function (event) {
					if(_this.locked) {
						event.preventDefault();
						//event.target.blur();
						return _this.error("LOCKED");
					}
					_this.selected = _this.get_node(event.target);
					_this.create();

					event.preventDefault();
					return false;
				})
				.listen("click", "img.tree-brother-add", function (event) {
					if(_this.locked) {
						event.preventDefault();
						return _this.error("LOCKED");
					}
					_this.selected = _this.get_node(event.target);
					_this.create(null, true);

					event.preventDefault();
					return false;
				})
				.listen("click", "img.tree-delete", function (event) {
					if(_this.locked) {
						event.preventDefault();
						return _this.error("LOCKED");
					}
					var node = _this.get_node(event.target);

					if (node.hasClass('deleted'))
					{
						if (window.confirm('������� ������������?'))
						{
							_this.selected = node;
							_this.remove();
						}
					}
					else
					{
						if (window.confirm('������� � �������?'))
						{
							node.addClass('deleted');
							node.find('li').addClass('deleted');
							_this.settings.callback.ondelete.call(null, node, this);
						}
					}

					event.preventDefault();
					return false;
				})
				.listen("dblclick", "a", function (event) { // WHEN DOUBLECLICK ON TEXT OR ICON
					if(_this.locked) {
						event.preventDefault();
						event.stopPropagation();
						event.target.blur();
						return _this.error("LOCKED");
					}
					_this.settings.callback.ondblclk.call(null, _this.get_node(event.target).get(0), _this);
					event.preventDefault();
					event.stopPropagation();
					event.target.blur();
				})
				.listen("contextmenu", "a", function (event) {
					if(_this.locked) return _this.error("LOCKED");
					return _this.settings.callback.onrgtclk.call(null, _this.get_node(event.target).get(0), _this, event);
				})
				.listen("mouseover", "a", function (event) {
					if(_this.locked) {
						event.preventDefault();
						event.stopPropagation();
						return _this.error("LOCKED");
					}
					if(_this.settings.ui.hover_mode && _this.hovered !== false && event.target.tagName == "A") {
						_this.hovered.children("a").removeClass("hover");
						_this.hovered = false;
					}
				})

				$('#treeBox li').live('mouseover',	function(){
					$(this).find('>a .js-tree-buttons img[hidden!=hidden]').show();
		           	//$(this).parent().siblings('img[hidden!=hidden]').hide();
		           	return false;
				}).live('mouseout', function(){
		           	$(this).find('>a .js-tree-buttons img[hidden!=hidden]').hide();
				});
				//$('#treeBox li > a').live('mouseover', function(){
				//	$(this).siblings('img[hidden!=hidden]').show();
				//});

				// ATTACH DRAG & DROP ONLY IF NEEDED
				if(this.settings.rules.draggable != "none" && this.settings.rules.dragrules != "none") {
					$(this.container)
						.listen("mousedown", "a", function (event) {
							if(_this.locked) return _this.error("LOCKED");
							// SELECT LIST ITEM NODE
							var obj = _this.get_node(event.target);
							// IF ITEM IS DRAGGABLE
							if(_this.settings.rules.multiple != false && _this.selected_arr.length > 1 && obj.children("a:eq(0)").hasClass("clicked")) {
								var counter = 0;
								for(i in _this.selected_arr) {
									if(_this.check("draggable", _this.selected_arr[i])) {
										_this.selected_arr[i].addClass("dragged");
										counter ++;
									}
								}
								if(counter > 0) {
									if(_this.check("draggable", obj))	_this._drag = obj;
									else								_this._drag = _this.container.find("li.dragged:eq(0)");
									_this.isdown	= true;
									_this.drag		= _this._drag.get(0).cloneNode(true);
									_this.drag.id	= "dragged";
									$(_this.drag).children("a").html("Multiple selection").end().children("ul").remove();
								}
							}
							else {
								if(_this.check("draggable", obj)) {
									_this._drag		= obj;
									_this.drag		= obj.get(0).cloneNode(true);
									_this.drag.id	= "dragged";
									_this.isdown	= true;
									obj.addClass("dragged");
								}
							}
							obj.blur();
							event.preventDefault();
							event.stopPropagation();
							return false;
						});
					$(document)
						.bind("mousedown", function (event) {
							event.stopPropagation();
							return true;
						})
						.bind("mouseup", function (event) {
							// CLEAR TIMEOUT FOR OPENING HOVERED NODES WHILE DRAGGING
							if(_this.to)	clearTimeout(_this.to);
							if(_this.sto)	clearTimeout(_this.sto);
							if(_this.drag && _this.drag.parentNode && _this.drag.parentNode == $(_this.container).get(0)) {
								$(_this.drag).remove();
								// CALL FUNCTION FOR COMPLETING MOVE
								if(_this.moveType) _this.moved(_this.container.find("li.dragged"), _this.moveRef, _this.moveType);
								_this.moveType = false;
								_this.moveRef = false;
							}
							// RESET EVERYTHING
							$("#marker").hide();
							_this._drag		= false;
							_this.drag		= false;
							_this.isdown	= false;
							_this.appended	= false;
							_this.container.find("li.dragged").removeClass("dragged");
							event.preventDefault();
							event.stopPropagation();
							return false;
						})
						.bind("mousemove", function (event) {
							if(_this.locked) return _this.error("LOCKED");
							if(_this.isdown) {
								// CLEAR TIMEOUT FOR OPENING HOVERED NODES WHILE DRAGGING
								if(_this.to) clearTimeout(_this.to);
								if(!_this.appended) {
									_this.container.append(_this.drag);
									_this.po = $(_this.drag).offsetParent().offset(); //{scroll : false}
									_this.appended = true;
								}
								$(_this.drag).css({ "left" : (event.pageX - _this.po.left - (_this.settings.ui.rtl ? $(_this.drag).width() : -5 ) ), "top" : (event.pageY - _this.po.top  + ($.browser.opera ? _this.container.scrollTop() : 0) + 15) });

								var cnt = $(event.target).parents(".tree:eq(0)");
								if(cnt.size() == 0) {
									if(_this.sto) clearTimeout(_this.sto);
									return false;
								}

								if(_this.sto) clearTimeout(_this.sto);
								_this.sto = setTimeout( function() { _this.scrollCheck(event.pageX,event.pageY, cnt); }, 50);

								var mov = false;
								var st = cnt.scrollTop();

								if(!_this.settings.rules.multitree && cnt.get(0) != _this.container.get(0)) return false;

								if(event.target.tagName == "A" ) {
									var goTo = {
										x : ($(event.target).offset().left - 1),//{scroll : false}
										y : (event.pageY - cnt.offset().top)    //{scroll : false}6
									}
									if(cnt.hasClass("rtl")) {
										goTo.x += $(event.target).width() - 8;
									}
									if( (goTo.y + st)%_this.li_height < _this.li_height/3 + 1 ) {
										mov = "before";
										goTo.y = event.pageY - (goTo.y + st)%_this.li_height - 2 ;
									}
									else if((goTo.y + st)%_this.li_height > _this.li_height*2/3 - 1 ) {
										mov = "after";
										goTo.y = event.pageY - (goTo.y + st)%_this.li_height + _this.li_height - 2 ;
									}
									else {
										mov = "inside";
										goTo.x -= 2;
										if(cnt.hasClass("rtl")) {
											goTo.x += 36;
										}
										goTo.y = event.pageY - (goTo.y + st)%_this.li_height + Math.floor(_this.li_height/2) - 2 ;
										if(_this.get_node(event.target).hasClass("closed")) {
											_this.to = setTimeout( function () { _this.open_branch(_this.get_node(event.target)); }, 500);
										}
									}

									if(_this.checkMove(cnt.find("li.dragged"),$(event.target),mov)) {
										if(mov == "inside")	$("#marker").attr("src", _this.images_path + "plus.gif").width(11);
										else {
											if(cnt.hasClass("rtl"))	{ $("#marker").attr("src", _this.images_path + "marker_rtl.gif").width(40); }
											else					{ $("#marker").attr("src", _this.images_path + "marker.gif").width(40); }
										}
										_this.moveType	= mov;
										_this.moveRef	= event.target;
										//$(_this.drag).children("IMG").remove();
										$(_this.drag).removeClass('js-drag-forbidden');
										$("#marker").css({ "left" : goTo.x , "top" : goTo.y }).show();
									}
									else {
										if($(_this.drag).children("IMG").size() == 0) {		
											//$(_this.drag).append("<img style='position:absolute; " + (_this.settings.ui.rtl ? "right" : "left" ) + ":4px; top:0px; background:white; padding:2px;' src='" + _this.images_path + "remove.png' />");
											$(_this.drag).addClass('js-drag-forbidden');
										}
										_this.moveType = false;
										_this.moveRef = false;
										$("#marker").hide();
									}
								}
								else if(event.target.tagName == "IMG" && event.target.id == "marker") {
									;
								}
								else {
									_this.moveType = false;
									_this.moveRef = false;
									$("#marker").hide();
								}
								event.preventDefault();
								event.stopPropagation();
								return false;
							}
							return true;
						});
				}
				// ENDIF OF DRAG & DROP FUNCTIONS
		},
		checkMove : function (NODES, REF_NODE, TYPE) {
			if(this.locked) return this.error("LOCKED");
			var _this = this;
			// OVER SELF OR CHILDREN
			if(REF_NODE.parents("li.dragged").size() > 0 || REF_NODE.is(".dragged")) return this.error("MOVE: NODE OVER SELF");
			// CHECK AGAINST DRAG_RULES
			if(NODES.size() == 1) {
				var NODE = NODES.eq(0);
				if(!this.check("dragrules", [NODE, TYPE, REF_NODE.parents("li:eq(0)")])) return this.error("MOVE: AGAINST DRAG RULES");
			}
			else {
				var ok = true;
				NODES.each(function (i) {
					if(ok == false) return false;
					if(i > 0) {
						var ref = NODES.eq( (i - 1) );
						var mv = "after";
					}
					else {
						var ref = REF_NODE;
						var mv = TYPE;
					}
					if(!_this.check("dragrules", [$(this), mv, ref])) ok = false;
				});
				if(ok == false) return this.error("MOVE: AGAINST DRAG RULES");
			}
			// CHECK AGAINST METADATA
			if(this.settings.rules.use_inline && this.settings.rules.metadata) {
				var nd = false;
				//window.console.log(REF_NODE.get(0));
				//window.console.log(NODES);
				if(TYPE == "inside")	nd = REF_NODE.parents("li:eq(0)");
				else					nd = REF_NODE.parents("li:eq(1)");

				function getMaxDepth(data)
				{
					var depth = 1;
					var subDepth = 0;
					data.each(function (i)
					{
						t = $(this);
						t = t.children("ul:eq(0)");
						if (t.size() > 0)
						{
							var d = getMaxDepth(t.children("li"));
							subDepth = Math.max(d, subDepth);
						}
					});
					return depth + subDepth;
				}

				// CHECK FOR MAXDEPTH UP THE CHAIN
				var incr = getMaxDepth(NODES);
				/*
				var incr = 0;
				NODES.each(function (i) {
					window.console.log(this);
					var i = 1;
					var t = $(this);
					while(i < 100) {
						t = t.children("ul:eq(0)");
						if(t.size() == 0) break;
						i++
					}
					incr = Math.max(i,incr);
				});
				*/

				if(nd.size())
				{
					// VALID CHILDREN CHECK
					if(typeof nd.metadata()["valid_children"] != "undefined") {
						var tmp = nd.metadata()["valid_children"];
						var ok = true;
						NODES.each(function (i) {
							if(ok == false) return false;
							if($.inArray(_this.get_type(this), tmp) == -1) ok = false;
						});
						if(ok == false) return this.error("MOVE: NOT A VALID CHILD");
					}
					// CHECK IF PARENT HAS FREE SLOTS FOR CHILDREN
					if(typeof nd.metadata()["max_children"] != "undefined") {
						if((nd.children("ul:eq(0)").children("li").not(".dragged").size() + NODES.size()) > nd.metadata().max_children) return this.error("MOVE: MAX CHILDREN REACHED");
					}

					var ok = true;

					nd.parents("li").each(function(i) {
						if(ok == false) return false;
						if($(this).metadata().max_depth) {
							if( (i + incr + 1) >= $(this).metadata().max_depth) ok = false;
						}
					});
					if(ok == false) return this.error("MOVE: MAX_DEPTH REACHED");
				}

				if (this.settings.max_depth)
				{
					var total = REF_NODE.parents("li").length - 1;
					total += incr;
					if (TYPE == 'inside')
					{
						total += 1;
					}

					if (total > this.settings.max_depth)
					{
						ok = false;
						return this.error("MOVE: MAX_DEPTH REACHED");
					}
				}
			}
			return true;
		},

		// USED AFTER REFRESH
		reselect : function () {
			var _this = this;
			// REOPEN BRANCHES
			if(this.opened && this.opened.length) {
				var opn = false;
				for(var j = 0; j < this.opened.length; j++) {
					if(this.settings.data.async) {
						if(this.get_node("#" + this.opened[j]).size() > 0) {
							opn = true;
							var tmp = this.opened[j];
							delete this.opened[j];
							this.open_branch("#" + tmp, true, function () { _this.reselect.apply(_this); } )
						}
						/*
						if(this.open_branch("#" + this.opened[j], true, function () { _this.reselect.apply(_this); } )) {
							opn = true;
							delete this.opened[j];
							$.log("Deleted");
						}
						*/
					}
					else this.open_branch("#" + this.opened[j], true);
				}
				if(this.settings.data.async && opn) return;
				delete this.opened;
			}
			// REPOSITION SCROLL - WHEN QUEUE IMPLEMENTED - SHOULD BE AT THE END
			if(this.scrtop) {
				this.container.scrollTop(_this.scrtop);
				delete this.scrtop;
			}
			// RESELECT PREVIOUSLY SELECTED OR DEFAULT
			if(this.settings.rules.multiple != false && this.selected_arr.length > 1) {
				var tmp = this.selected_arr;
				this.selected_arr = [];
				for(i in tmp) {
					this.select_branch("#" + tmp[i].attr("id"), true);
				}
			}
			else if(this.settings.dflt && $(this.settings.dflt).size() == 1) {
				this.selected		= $(this.settings.dflt);
				this.settings.dflt	= false;
				this.select_branch(this.selected);
			}
			this.settings.callback.onload.call(null, _this);
		},
		// GET THE EXTENDED LI ELEMENT
		get_node : function (obj) {
			var obj = $(obj);
			return obj.is("li") ? obj : obj.parents("li:eq(0)");
		},
		// GET THE TYPE OF THE NODE
		get_type : function (obj) {
			obj = !obj ? this.selected : this.get_node(obj);
			if(!obj) return;
			if(this.settings.rules.metadata) {
				$.metadata.setType("attr", this.settings.rules.metadata);
				return obj.metadata().type;
			}
			else return obj.attr(this.settings.rules.type_attr);
		},
		// SCROLL CONTAINER WHILE DRAGGING
		scrollCheck : function (x,y, cntr) {
			var _this = this;
			if(!cntr) {
				var cnt = _this.container;
				var off = _this.offset;
			} else {
				var cnt = cntr;
				var off = cnt.offset();
			}
			// NEAR TOP
			if(y - off.top < 20) {
				cnt.scrollTop(Math.max(cnt.scrollTop()-4,0));
			}
			// NEAR BOTTOM (DETECT HORIZONTAL SCROLL)
			var h_cor = (cnt.get(0).scrollWidth > cnt.width()) ? 40 : 20;
			if(cnt.height() - (y - off.top) < h_cor) {
				cnt.scrollTop(cnt.scrollTop()+4);
			}
			// NEAR LEFT
			if(x - off.left < 20) {
				cnt.scrollLeft(cnt.scrollLeft()-4);
			}
			// NEAR RIGHT
			if(cnt.width() - (x - off.left) < 40) {
				cnt.scrollLeft(cnt.scrollLeft()+4);
			}
			_this.sto = setTimeout( function() { _this.scrollCheck(x,y,cntr); }, 50);
		},
		check : function (rule, nodes) {
			if(this.locked) return this.error("LOCKED");
			// CHECK LOCAL RULES IF METADATA
			if(rule != "dragrules" && this.settings.rules.use_inline && this.settings.rules.metadata) {
				$.metadata.setType("attr", this.settings.rules.metadata);
				if(typeof this.get_node(nodes).metadata()[rule] != "undefined") return this.get_node(nodes).metadata()[rule];
			}
			if(!this.settings.rules[rule])			return false;
			if(this.settings.rules[rule] == "none")	return false;
			if(this.settings.rules[rule] == "all")	return true;
			if(rule == "dragrules") {
				var nds = new Array();
				nds[0] = this.get_type(nodes[0]);
				nds[1] = nodes[1];

				nds[2] = this.get_type(nodes[2]);
				for(var i = 0; i < this.settings.rules.dragrules.length; i++) {
					var tmp = this.settings.rules.dragrules[i].split(" ");
					for(var j = 0; j < 3; j++) {
						if(tmp[j] == nds[j] || tmp[j] == "*") tmp[j] = true;
					}
					if(tmp[0] === true && tmp[1] === true && tmp[2] === true) return true;
				}
				return false;
			}
			else
				return ($.inArray(this.get_type(nodes),this.settings.rules[rule]) != -1) ? true : false;
		},
		hover_branch : function (obj) {
			if(this.locked) return this.error("LOCKED");
			if(this.settings.ui.hover_mode == false) return this.select_branch(obj);
			var _this = this;
			var obj = _this.get_node(obj);
			if(!obj.size()) return this.error("HOVER: NOT A VALID NODE");
			// CHECK AGAINST RULES FOR SELECTABLE NODES
			if(!_this.check("clickable", obj)) return this.error("SELECT: NODE NOT SELECTABLE");
			if(this.hovered) this.hovered.children("A").removeClass("hover");

			// SAVE NEWLY SELECTED
			this.hovered = obj;

			// FOCUS NEW NODE AND OPEN ALL PARENT NODES IF CLOSED
			this.hovered.children("a").removeClass("hover").addClass("hover");

			// SCROLL SELECTED NODE INTO VIEW
			var off_t = this.hovered.offset().top;
			var beg_t = this.container.offset().top;
			var end_t = beg_t + this.container.height();
			var h_cor = (this.container.get(0).scrollWidth > this.container.width()) ? 40 : 20;
			if(off_t + 5 < beg_t) this.container.scrollTop(this.container.scrollTop() - (beg_t - off_t + 5) );
			if(off_t + h_cor > end_t) this.container.scrollTop(this.container.scrollTop() + (off_t + h_cor - end_t) );
		},
		select_branch : function (obj, multiple) {
			if(this.locked) return this.error("LOCKED");
			if(!obj && this.hovered !== false) obj = this.hovered;
			var _this = this;
			obj = _this.get_node(obj);
			if(!obj.size()) return this.error("SELECT: NOT A VALID NODE");
			obj.children("a").removeClass("hover");
			// CHECK AGAINST RULES FOR SELECTABLE NODES
			if(!_this.check("clickable", obj)) return this.error("SELECT: NODE NOT SELECTABLE");
			if(_this.settings.callback.beforechange.call(null,obj.get(0),_this) === false) return this.error("SELECT: STOPPED BY USER");
			// IF multiple AND obj IS ALREADY SELECTED - DESELECT IT
			if(this.settings.rules.multiple != false && multiple && obj.children("a.clicked").size() > 0) {
				return this.deselect_branch(obj);
			}
			if(this.settings.rules.multiple != false && multiple) {
				this.selected_arr.push(obj);
			}
			if(this.settings.rules.multiple != false && !multiple) {
				for(i in this.selected_arr) {
					this.selected_arr[i].children("A").removeClass("clicked");
				}
				this.selected_arr = [];
				this.selected_arr.push(obj);
				if(this.selected) this.selected.children("A").removeClass("clicked");
			}
			if(!this.settings.rules.multiple) {
				if(this.selected) this.selected.children("A").removeClass("clicked");
			}
			// SAVE NEWLY SELECTED
			this.selected = obj;
			if(this.settings.ui.hover_mode && this.hovered !== false) {
				this.hovered.children("A").removeClass("hover");
				this.hovered = obj;
			}

			// FOCUS NEW NODE AND OPEN ALL PARENT NODES IF CLOSED
			this.selected.children("a").removeClass("clicked").addClass("clicked").end().parents("li.closed").each( function () { _this.open_branch(this, true); });

			// SCROLL SELECTED NODE INTO VIEW
			var off_t = this.selected.offset().top;
			var beg_t = this.container.offset().top;
			var end_t = beg_t + this.container.height();
			var h_cor = (this.container.get(0).scrollWidth > this.container.width()) ? 40 : 20;
			if(off_t + 5 < beg_t) this.container.scrollTop(this.container.scrollTop() - (beg_t - off_t + 5) );
			if(off_t + h_cor > end_t) this.container.scrollTop(this.container.scrollTop() + (off_t + h_cor - end_t) );

			this.set_cookie("selected");
			this.settings.callback.onchange.call(null, this.selected.get(0), _this);
		},
		deselect_branch : function (obj) {
			if(this.locked) return this.error("LOCKED");
			var _this = this;
			var obj = this.get_node(obj);
			obj.children("a").removeClass("clicked");
			if(this.settings.rules.multiple != false && this.selected_arr.length > 1) {
				this.selected_arr = [];
				this.container.find("a.clicked").filter(":first-child").parent().each(function () {
					_this.selected_arr.push($(this));
				});
				if(obj.get(0) == this.selected.get(0)) {
					this.selected = this.selected_arr[0];
					this.set_cookie("selected");
				}
			}
			else {
				if(this.settings.rules.multiple != false) this.selected_arr = [];
				this.selected = false;
				this.set_cookie("selected");
			}
			if(this.selected)	this.settings.callback.onchange.call(null, this.selected.get(0), _this);
			else				this.settings.callback.onchange.call(null, false, _this);
		},
		toggle_branch : function (obj) {
			if(this.locked) return this.error("LOCKED");
			var obj = this.get_node(obj);
			if(obj.hasClass("closed"))	return this.open_branch(obj);
			if(obj.hasClass("open"))	return this.close_branch(obj);
		},
		open_branch : function (obj, disable_animation, callback) {
			if(this.locked) return this.error("LOCKED");
			var obj = this.get_node(obj);
			if(!obj.size()) return this.error("OPEN: NO SUCH NODE");

			if(this.settings.data.async && obj.find("li").size() == 0) {
				var _this = this;
				obj.children("ul:eq(0)").remove().end().append("<ul><li class='last'><a style='background-image:url(" + _this.images_path + "throbber.gif)' href='#'>" + (_this.settings.lang.loading || "Loading ...") + "</a></li></ul>");
				obj.removeClass("closed").addClass("open");
				if(this.settings.data.type == "xml_flat" || this.settings.data.type == "xml_nested") {
					var xsl = (this.settings.data.type == "xml_flat") ? "flat.xsl" : "nested.xsl";
					var str = (this.settings.data.url.indexOf("?") == -1) ? "?id=" + encodeURIComponent(obj.attr("id")) : "&id=" + encodeURIComponent(obj.attr("id"));
					obj.children("ul:eq(0)").getTransform(this.path + xsl, this.settings.data.url + str, { repl : true, callback: function (str, json) {
							if(str.length < 10) {
								obj.removeClass("closed").removeClass("open").addClass("leaf").children("ul").remove();
								if(callback) callback.call();
								return;
							}
							_this.open_branch.apply(_this, [obj]);
							if(callback) callback.call();
						}
					});
				}
				else {
					$.getJSON(this.settings.data.url, { id : obj.attr("id") }, function (data, textStatus) {
						if(!data || data.length == 0) {
							obj.removeClass("closed").removeClass("open").addClass("leaf").children("ul").remove();
							if(callback) callback.call();
							return;
						}
						var str = "";
						if(data.length) {
							for(var i = 0; i < data.length; i++) {
								str += _this.parseJSON(data[i]);
							}
						}
						else str = _this.parseJSON(data);
						obj.children("ul:eq(0)").replaceWith("<ul>" + str + "</ul>");
						obj.find("li:last-child").addClass("last").end().find("li:has(ul)").not(".open").addClass("closed");
						obj.find("li").not(".open").not(".closed").addClass("leaf");
						_this.open_branch.apply(_this, [obj]);
						if(callback) callback.call();
					});
				}
				return true;
			}
			else {
				if(parseInt(this.settings.ui.animation) > 0 && !disable_animation && !($.browser.msie && $.browser.version < 7) ) {
					obj.children("ul:eq(0)").css("display","none");
					obj.removeClass("closed").addClass("open");
					obj.children("ul:eq(0)").slideDown(parseInt(this.settings.ui.animation), function() {
						$(this).css("display","");
						if(callback) callback.call();
					});
				} else {
					obj.removeClass("closed").addClass("open");
					if(callback) callback.call();
				}
				this.set_cookie("open");
				this.settings.callback.onopen.call(null, obj.get(0), this);
				return true;
			}
		},
		close_branch : function (obj, disable_animation) {
			if(this.locked) return this.error("LOCKED");
			var _this = this;
			var obj = this.get_node(obj);
			if(parseInt(this.settings.ui.animation) > 0 && !disable_animation && !($.browser.msie && $.browser.version < 7) && obj.children("ul:eq(0)").size() == 1) {
				obj.children("ul:eq(0)").slideUp(parseInt(this.settings.ui.animation), function() {
					obj.removeClass("open").addClass("closed");
					_this.set_cookie("open");
					$(this).css("display","");
				});
			}
			else {
				obj.removeClass("open").addClass("closed");
				this.set_cookie("open");
			}
			if(this.selected && obj.children("ul:eq(0)").find("a.clicked").size() > 0) {
				obj.find("li:has(a.clicked)").each(function() {
					_this.deselect_branch(this);
				});
				if(obj.children("a.clicked").size() == 0) this.select_branch(obj, (this.settings.rules.multiple != false && this.selected_arr.length > 0) );
			}
			this.settings.callback.onclose.call(null, obj.get(0), this);
		},
		open_all : function (obj) {
			if(this.locked) return this.error("LOCKED");
			var _this = this;
			obj = obj ? $(obj) : this.container;
			obj.find("li.closed").each( function () { var __this = this; _this.open_branch.apply(_this, [this, true, function() { _this.open_all.apply(_this, [__this]); } ]); });
		},
		close_all : function () {
			if(this.locked) return this.error("LOCKED");
			var _this = this;
			$(this.container).find("li.open").each( function () { _this.close_branch(this); });
		},
		show_lang : function (i) {
			if(this.locked) return this.error("LOCKED");
			if(this.settings.languages[i] == this.current_lang) return true;
			var st = false;
			var id = this.container.attr("id") ? "#" + this.container.attr("id") : ".tree";
			st = get_css(id + " ." + this.current_lang, this.sn);
			if(st !== false) st.style.display = "none";
			st = get_css(id + " ." + this.settings.languages[i], this.sn);
			if(st !== false) st.style.display = "block";
			this.current_lang = this.settings.languages[i];
			return true;
		},
		cycle_lang : function() {
			if(this.locked) return this.error("LOCKED");
			var i = $.inArray(this.current_lang, this.settings.languages);
			i ++;
			if(i > this.settings.languages.length - 1) i = 0;
			this.show_lang(i);
		},
		create : function (type, afterSelected) {
			if(this.locked) return this.error("LOCKED");
			// NOTHING SELECTED
			if(!this.selected) return this.error("CREATE: NO NODE SELECTED");

			if(!this.check("creatable", this.selected)) return this.error("CREATE: CANNOT CREATE IN NODE");

			var t = type || this.get_type();
			if(this.settings.rules.use_inline && this.settings.rules.metadata) {
				$.metadata.setType("attr", this.settings.rules.metadata);
				if(typeof this.selected.metadata()["valid_children"] != "undefined") {
					if($.inArray(t, this.selected.metadata()["valid_children"]) == -1) return this.error("CREATE: NODE NOT A VALID CHILD");
				}
				if(typeof this.selected.metadata()["max_children"] != "undefined") {
					if( (this.selected.children("ul:eq(0)").children("li").size() + 1) > this.selected.metadata().max_children) return this.error("CREATE: MAX_CHILDREN REACHED");
				}
				var ok = true;

                if (this.settings.max_depth !== undefined)
				{
					var total = this.selected.parents("li").length;
					if (total >= this.settings.max_depth)
					{
						ok = false;
					}
				}

				if(!ok) return this.error("CREATE: MAX_DEPTH REACHED");
			}



			if(this.selected.hasClass("closed") && !afterSelected) {
				var _this = this;
				return this.open_branch(this.selected, true, function () { _this.create.apply(_this, [type]); } );
			}

			$li = $("<li></li>");
			// NEW NODE IS OF PASSED TYPE OR PARENT'S TYPE
			if(this.settings.rules.metadata) {
				$.metadata.setType("attr", this.settings.rules.metadata);
				$li.attr(this.settings.rules.metadata, "type: '" + t + "'");
			}
			else {
				$li.attr(this.settings.rules.type_attr, t)
			}
			if(this.settings.languages.length) {
				for(i = 0; i < this.settings.languages.length; i++) {
					var val = "New folder";
					if(this.settings.lang.new_node) {
						if((typeof this.settings.lang.new_node).toLowerCase() != "string" && this.settings.lang.new_node[i]) val = this.settings.lang.new_node[i];
						else val = this.settings.lang.new_node;
					}
					$li.append("<a href='#' class='" + this.settings.languages[i] + "'>" + val + "</a>");
				}
			}
			else { $li.append("<a href='#'>" + (this.settings.lang.new_node || "New folder") + "</a>"); }
			$li.addClass("leaf hidden");

			var currentLevel = this.selected.parents("li").length + 1;
			if (afterSelected !== true)
			{
				currentLevel++;
			}


            str = this.span_buttons;
			//$li.find('>a').append(str);
			//console.log('create');

			$li.hover(
				function(){
					$(this).find('>a .js-tree-buttons img[hidden!=hidden]').show();
	             	$(this).parent().siblings('img[hidden!=hidden]').hide();
	             	return false;
				},
				function(){
	             	$(this).find('>a .js-tree-buttons img[hidden!=hidden]').hide();
				}
			);

			$li.find('>a').mouseover(function(){
				$(this).siblings('img[hidden!=hidden]').show();
			});

			if (afterSelected === true)
			{
				this.moved($li,this.selected.children("a:eq(0)"),"after",true);
			}
			else if(this.settings.rules.createat == "top" || this.selected.children("ul").size() == 0) {
				this.moved($li,this.selected.children("a:eq(0)"),"inside", true);
			}
			else {
				this.moved($li,this.selected.children("ul:eq(0)").children("li:last").children("a:eq(0)"),"after",true);
			}
			this.select_branch($li.children("a:eq(0)"));
			this.rename();
			return $li;
		},
		rename : function () {
			if(this.locked) return this.error("LOCKED");
			if(this.selected) {
				var _this = this;
				if(!this.check("renameable", this.selected)) return this.error("RENAME: NODE NOT RENAMABLE");
				if(!this.settings.callback.beforerename.call(null,this.selected.get(0), _this.current_lang, _this)) return this.error("RENAME: STOPPED BY USER");
				var obj = this.selected;
				if(this.current_lang)	obj = obj.find("a." + this.current_lang).get(0);
				else					obj = obj.find("a:first").get(0);
				last_value = $(obj).text();

				/*
				var w_max = 170;
				var w_min =  10;
				var c_wid = (obj.offsetWidth - 25);
				c_wid = Math.max(w_min,c_wid);
				c_wid = Math.min(w_max,c_wid);
				*/

				_this.inp = $("<input type='text' />");
				_this.inp
					.val(last_value)
					.bind("mousedown",		function (event) { event.stopPropagation(); })
					.bind("mouseup",		function (event) { event.stopPropagation(); })
					.bind("click",			function (event) { event.stopPropagation(); })
					.bind("keyup",			function (event) {
							var key = event.keyCode || event.which;
							if(key == 27) { this.blur(); return }
							if(key == 13) { this.blur(); return }
						});
				_this.inp.blur(function(event) {
						if(this.value == "") this.value == last_value;
						
						$(obj).html( $(obj).parent().find("input").eq(0).attr("value") ).get(0).style.display = "";
						$(obj).prevAll("span").remove();
						$(obj).append($(_this.span_buttons));
						_this.settings.callback.onrename.call(null, _this.get_node(obj).get(0), _this.current_lang, _this);
						_this.inp = false;
					});
				var spn = $("<span />").addClass(obj.className).append(_this.inp);
				spn.attr("style", $(obj).attr("style"));
				obj.style.display = "none";
				$(obj).parent().prepend(spn);
				_this.inp.get(0).focus();
				_this.inp.get(0).select();
			}
			else return this.error("RENAME: NO NODE SELECTED");
		},
		// REMOVE NODES
		remove : function() {
			if(this.locked) return this.error("LOCKED");
			if(this.selected) {
				if(!this.check("deletable", this.selected)) return this.error("DELETE: NODE NOT DELETABLE");
				if(!this.settings.callback.beforedelete.call(null,this.selected.get(0), _this)) return this.error("DELETE: STOPPED BY USER");
				$parent = this.selected.parent();
				var obj = this.selected;
				if(!this.settings.rules.multiple != false || this.selected_arr.length == 1) {
					var stop = true;
					this.get_prev();
				}
				obj = obj.remove();
				$parent.children("li:last").addClass("last");
				if($parent.children("li").size() == 0) {
					$li = $parent.parents("li:eq(0)");
					$li.removeClass("open").removeClass("closed").addClass("leaf").children("ul").remove();
					this.set_cookie("open");
				}
				this.selected = false;
				this.settings.callback.ondelete.call(null, obj, this);
				if(this.settings.rules.multiple != false && !stop) {
					var _this = this;
					this.selected_arr = [];
					this.container.find("a.clicked").filter(":first-child").parent().each(function () {
						_this.selected_arr.push($(this));
					});
					if(this.selected_arr.length > 0) {
						this.selected = this.selected_arr[0];
						this.remove();
					}
				}
			}
			else return this.error("DELETE: NO NODE SELECTED");
		},
		// FOR EXPLORER-LIKE KEYBOARD SHORTCUTS
		get_next : function() {
			var obj = this.hovered || this.selected;
			if(obj) {
				if(obj.hasClass("open"))					return this.hover_branch(obj.find("li:eq(0)"));
				else if($(obj).nextAll("li").size() > 0)	return this.hover_branch(obj.nextAll("li:eq(0)"));
				else										return this.hover_branch(obj.parents("li").next("li").eq(0));
			}
		},
		get_prev : function() {
			var obj = this.hovered || this.selected;
			if(obj) {
				if(obj.prev("li").size()) {
					var obj = obj.prev("li").eq(0);
					while(obj.hasClass("open")) obj = obj.children("ul:eq(0)").children("li:last");
					return this.hover_branch(obj);
				}
				else { return this.hover_branch(obj.parents("li:eq(0)")); }
			}
		},
		get_left : function() {
			var obj = this.hovered || this.selected;
			if(obj) {
				if(obj.hasClass("open"))	this.close_branch(obj);
				else						this.get_prev();
			}
		},
		get_right : function() {
			var obj = this.hovered || this.selected;
			if(obj) {
				if(obj.hasClass("closed"))	this.open_branch(obj);
				else						this.get_next();
			}
		},
		toggleDots : function () {
			this.container.toggleClass("no_dots");
		},
		set_cookie : function (type) {
			if(this.settings.cookies === false) return false;
			switch(type) {
				case "selected":
					var val = this.selected ? this.selected.attr("id") : false;
					$.cookie(this.settings.cookies.prefix + '_selected',val,this.settings.cookies.opts);
					break;
				case "open":
					var str = "";
					this.container.find("li.open").each(function (i) { str += this.id + ","; });
					$.cookie(this.settings.cookies.prefix + '_open',str.replace(/,$/ig,""),this.settings.cookies.opts);
					break;
			}
		},
		moved : function (what, where, how, is_new, is_copy) {
			var what	= $(what);
			var $parent	= $(what).parents("ul:eq(0)");
			var $where	= $(where);
			// IF MULTIPLE
			if(what.size() > 1) {
				var _this = this;
				var tmp = this.moved(what.eq(0),where,how, false, is_copy);
				what.each(function (i) {
					if(i == 0) return;
					tmp = _this.moved(this, tmp.children("a:eq(0)"), "after");
				})
				return;
			}
			if(is_new) {
				if(!this.settings.callback.beforecreate.call(null,this.get_node(what).get(0), this.get_node(where).get(0),how,this)) return;
			}
			else {
				if(!this.settings.callback.beforemove.call(null,this.get_node(what).get(0), this.get_node(where).get(0),how,this)) return;
			}

			// ADD NODE TO NEW PLACE
			switch(how) {
				case "before":
					$where.parents("ul:eq(0)").children("li.last").removeClass("last");
					$where.parent().before(what.removeClass("last"));
					$where.parents("ul:eq(0)").children("li:last").addClass("last");
					break;
				case "after":
					$where.parents("ul:eq(0)").children("li.last").removeClass("last");
					$where.parent().after(what.removeClass("last"));
					$where.parents("ul:eq(0)").children("li:last").addClass("last");
					break;
				case "inside":
					if(this.settings.data.async) {
						var obj = this.get_node($where);
						if(obj.hasClass("closed")) {
							var _this = this;
							return this.open_branch(obj, true, function () { _this.moved.apply(_this, [what, where, how, is_new, is_copy]); })
						}
					}
					if($where.parent().children("ul:first").size()) {
						if(this.settings.rules.createat == "top")	$where.parent().children("ul:first").prepend(what.removeClass("last")).children("li:last").addClass("last");
						else										$where.parent().children("ul:first").children(".last").removeClass("last").end().append(what.removeClass("last")).children("li:last").addClass("last");
					}
					else {
						what.addClass("last");
						$where.parent().append("<ul/>").removeClass("leaf").addClass("closed");
						$where.parent().children("ul:first").prepend(what);
					}
					if(!this.settings.data.async) {
						this.open_branch($where);
					}
					break;
				default:
					break;
			}
			// CLEANUP OLD PARENT
			if($parent.find("li").size() == 0) {
				var $li = $parent.parent();
				$li.removeClass("open").removeClass("closed").addClass("leaf").children("ul").remove();
				$li.parents("ul:eq(0)").children("li.last").removeClass("last").end().children("li:last").addClass("last");
				this.set_cookie("open");
			}
			else {
				$parent.children("li.last").removeClass("last");
				$parent.children("li:last").addClass("last");
			}

			//
			var node = this.get_node(what);
			var currentLevel = node.parents("li").length + 1;

			if (this.settings['hide_buttons']['edit'] !== undefined && this.settings['hide_buttons']['edit'] !== true)
			{
				if (this.settings['hide_buttons']['edit'][currentLevel]) {
					$('a .js-tree-buttons img.tree-edit',node).get(0).style.display = 'none';
					$('a .js-tree-buttons img.tree-edit',node).attr('hidden','hidden');
					
				} else {
				    //console.log(node)
					//$('a .js-tree-buttons img.tree-edit',node).get(0).style.display = '';
					$('.js-tree-buttons img.tree-edit',node).hide();
					$('.js-tree-buttons img.tree-edit',node).removeAttr('hidden');
				}
			}

			if (this.settings['hide_buttons']['addChild'] !== undefined && this.settings['hide_buttons']['addChild'] !== true)
			{
				if (this.settings['hide_buttons']['addChild'][currentLevel]) {
//					$('.js-tree-buttons img.tree-child-add',node).get(0).style.display = 'none';
                    $('.js-tree-buttons img.tree-child-add',node).hide();
					$('.js-tree-buttons img.tree-child-add',node).attr('hidden','hidden');
				} else {
					//$('.js-tree-buttons img.tree-child-add',node).get(0).style.display = '';
					$('.js-tree-buttons img.tree-child-add',node).hide();
					$('.js-tree-buttons img.tree-child-add',node).removeAttr('hidden');
				}
			}

			if (this.settings['hide_buttons']['addBrother'] !== undefined && this.settings['hide_buttons']['addBrother'] !== true)
			{
				if (this.settings['hide_buttons']['addBrother'][currentLevel]) {
					$('.js-tree-buttons img.tree-brother-add',node).get(0).style.display = 'none';
					$('.js-tree-buttons img.tree-brother-add',node).attr('hidden','hidden');
				} else {
					//$('.js-tree-buttons img.tree-brother-add',node).get(0).style.display = '';
					$('.js-tree-buttons img.tree-brother-add',node).hide();
					$('.js-tree-buttons img.tree-brother-add',node).removeAttr('hidden');
				}
			}

			if (this.settings['hide_buttons']['del'] !== undefined && this.settings['hide_buttons']['del'] !== true)
			{
				if (this.settings['hide_buttons']['del'][currentLevel]) {
					$('.js-tree-buttons img.tree-delete',node).get(0).style.display = 'none';
					$('.js-tree-buttons img.tree-delete',node).get(0).style.display = 'none';
				} else {
					//$('.js-tree-buttons img.tree-delete',node).get(0).style.display = '';
					$('.js-tree-buttons img.tree-delete',node).hide();
					$('.js-tree-buttons img.tree-delete',node).removeAttr('hidden');
				}
			}

			if (!this.container.hasClass('no-controls'))
			{
				this.container.toggleClass("no-controls");
				this.container.toggleClass("no-controls");
			}
			//

			if(is_new && how != "inside") where = this.get_node(where).parents("li:eq(0)");
			if(is_copy)		this.settings.callback.oncopy.call(null, this.get_node(what).get(0), this.get_node(where).get(0), how, this)
			else if(is_new)	this.settings.callback.oncreate.call(null, this.get_node(what).get(0), this.get_node(where).get(0), this.settings.insertAt, this);
			else			this.settings.callback.onmove.call(null, this.get_node(what).get(0), this.get_node(where).get(0), how, this);
			return what;
		},
		error : function (code) {
			this.settings.callback.error.call(null,code,this);
			return false;
		},
		lock : function (state) {
			this.locked = state;
			if(this.locked)	this.container.addClass("locked");
			else			this.container.removeClass("locked");
		},
		cut : function () {
			if(this.locked) return this.error("LOCKED");
			if(!this.selected) return this.error("CUT: NO NODE SELECTED");
			this.copy_nodes = false;
			this.cut_nodes = this.container.find("a.clicked").filter(":first-child").parent();
		},
		copy : function () {
			if(this.locked) return this.error("LOCKED");
			if(!this.selected) return this.error("COPY: NO NODE SELECTED");
			this.copy_nodes = this.container.find("a.clicked").filter(":first-child").parent();
			this.cut_nodes = false;
		},
		paste : function () {
			if(this.locked) return this.error("LOCKED");
			if(!this.selected) return this.error("PASTE: NO NODE SELECTED");
			if(!this.copy_nodes && !this.cut_nodes) return this.error("PASTE: NOTHING TO DO");
			if(this.copy_nodes && this.copy_nodes.size()) {
				if(!this.checkMove(this.copy_nodes, this.selected.children("a:eq(0)"), "inside")) return false;
				tmp = this.copy_nodes.clone();
				tmp.each(function (i) {
					this.id = this.id + "_copy";
					$(this).find("li").each(function () {
						this.id = this.id + "_copy";
					})
				});
				this.moved(tmp, this.selected.children("a:eq(0)"), "inside", false, true);
				this.copy_nodes = false;
			}
			if(this.cut_nodes && this.cut_nodes.size()) {
				if(!this.checkMove(this.cut_nodes, this.selected.children("a:eq(0)"), "inside")) return false;
				this.moved(this.cut_nodes, this.selected.children("a:eq(0)"), "inside");
				this.cut_nodes = false;
			}
		}
	}
}
