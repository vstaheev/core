Multiselect = Class.create();
Multiselect.prototype = {
	url: '',
	images: '',			// ���� �� ��������
	cont: null, 		// ���������
	table: null,		// ������� ������ ����������, � ������� ����� ������
	timeout: null,
	currentRow : -1,	// ����� ������� ������ � �������
	updateCont: null,
	
	initialize: function(){	},
	
	init: function(params)
	{
		this.cont = $('#' + params.cont);
		this.updateCont = $('#' + params.updateCont);
		this.table = document.createElement('table');
		this.cont.append(this.table);
		this.url = params.url;
		this.images = params.images;
		
		this.loadData();
	},
	
	/**
	 * load JSON data from `url`
	 */
	loadData: function()
	{
		this.showUpdateMsg();
		var params = {'a' : 'list'};
		$.post(this.url, params, this.parseData.bind(this), 'json');
	},
	
	parseData: function(data)
	{
		this.hideUpdateMsg();
		var i = null;
		for(i in data)
		{
			this.addNewRow(data[i]);
		}
	},
	
	addNewRow: function(data)
	{
		var row = this.table.insertRow(++this.currentRow);
		row.setAttribute('item_id', data.id);
		
		// sortable things
		var cell = row.insertCell(-1);
		var img = document.createElement('img');
		img.src = this.images + 'up_arrow.gif';
		img.setAttribute('title', '����������� ����');
		img.style.cursor = 'pointer';
		$(img).bind('click', this.moveUp.bind(this, row));
		cell.appendChild(img);
		var img = document.createElement('img');
		img.src = this.images + 'down_arrow.gif';
		img.setAttribute('title', '����������� ����');
		img.style.cursor = 'pointer';
		$(img).bind('click', this.moveDown.bind(this, row));
		cell.appendChild(img);

		// title			
		var cell = row.insertCell(-1);
		cell.innerHTML = data.title;
		
		// delete
		var cell = row.insertCell(-1);
		var span = document.createElement('span');
		span.innerHTML = 'x';
		span.style.cursor = 'pointer';
		$(span).bind('click', this.delItem.bind(this, data.id, row));
		cell.appendChild(span);
	},
	
	addItem: function(id, title)
	{
		this.showUpdateMsg();
		var params = {'a' : 'add', 'item_id' : id};
		$.post(this.url, params, this._addItem.bind(this, title), 'json');
	},
	
	_addItem: function(title, data)
	{
		this.hideUpdateMsg();
		
		if(parseInt(data.id, 10) > 0)
		{
			this.addNewRow({'id' : data.id, 'title' : title});
		}
	},
	
	delItem: function(id, row)
	{
		this.showUpdateMsg();
		var params = {'a' : 'del', 'item_id' : id};
		$.post(this.url, params, this._delItem.bind(this, row), 'json');
		
	},
	
	_delItem: function(row)
	{
		this.hideUpdateMsg();
		this.table.deleteRow(row.rowIndex);
	},
	
	showUpdateMsg: function()
	{
		this.updateCont.css('display', '');
	},
	
	hideUpdateMsg: function()
	{
		this.updateCont.css('display', 'none');
	},
	
	moveUp: function(curNode)
	{
		var beforeNode = curNode.previousSibling;
        if(!beforeNode || beforeNode.getAttribute('noswap') == 1) 
        {
                return false;
        }

        this.saveOrder();
        curNode.parentNode.insertBefore(curNode, beforeNode);
	},
	
	moveDown: function(curNode)
	{
		var nextNode = curNode.nextSibling;
        if(!nextNode || nextNode.getAttribute('noswap') == 1)
        {
        	return false;
        }
        this.saveOrder();
        
        var nnextNode = nextNode.nextSibling;
        
        if(nnextNode)   
        {
                curNode.parentNode.insertBefore(curNode, nnextNode);
        }
        else
        {
                curNode.parentNode.appendChild(curNode);
        }
	},
	
	saveOrder : function()
	{
		if(this.timeout)
		{
			clearTimeout(this.timeout);
		}
		this.timeout = setTimeout(this._saveOrder.bind(this), 2000);
	},
	
	_saveOrder: function()
	{
		var i = null;
		var order = 0;
		var params = {};
		
		$("tr", this.table).each(function(){
			var id = this.getAttribute('item_id');
			id = parseInt(id, 10);
			if(id > 0)
			{
				params[id] = order++;
			}
		})
		
		this.showUpdateMsg();
		params.a = 'changeorder';
		$.post(this.url, params, this.hideUpdateMsg.bind(this), 'text');
	}
};