Gallery = Class.create();
Gallery.prototype = {

    baseUrl: '',
    selectedItems: [],
    lastSelected: null,
    formPrefix: '',

    initialize: function(){},

    init: function() {
        var self = this;

		if (!this.noDrags) {
			$('#gallery').sortable({
				start: function(e,ui) {
					Gallery.dragged = true;
				},
				stop: function() {
					var order = '';
					$('#gallery div.gallery-image').each(function() {
						order += self.getId(this)+',';
					});
					order = order.substr(0, order.length-1);
					$.post(self.baseUrl + '?id='+self.rubricId, {
						'action': 'reorder',
						'order': order
					},function(data) {
						if (!data.ok) alert('������!');
					},'json');
				},
				items: '> div.gallery-image'
			});	
		}

        //control for upload images
        imagesUploadSettings.upload_url = this.baseUrl+'?id='+this.rubricId+'&session_hash='+this.sessionHash;
        imagesUploadSettings.flash_url = this.imagesUrl+"swfupload.swf";
        imagesUploadSettings.button_width = this.thumbWidth;
        imagesUploadSettings.button_height = this.thumbHeight;
        imagesUploadSettings.file_types = this.fileExtensions;
        imagesUploadSettings.file_post_name = this.formPrefix + "Filedata";
        this.swfUpload = new SWFUpload(imagesUploadSettings);
        this.swfUpload.customSettings.gallery = this;

        //control for replace one image
        /*imageOneUploadSettings.upload_url = this.baseUrl+'?id='+this.rubricId+'&session_hash='+this.sessionHash;
        imageOneUploadSettings.flash_url = this.imagesUrl+"swfupload.swf";
        imageOneUploadSettings.file_types = this.fileExtensions;
        imageOneUploadSettings.file_post_name = this.formPrefix + "Filedata";
        this.swfUploadOne = new SWFUpload(imageOneUploadSettings);
        this.swfUploadOne.customSettings.gallery = this;*/
		
        //control buttons
        $('#gallery div.gallery-image').each(function() {
            self.initImage(this);
        });

        //edit image form
        $('#editImageOK').click(this.editImageTitle.prototypeBind(this));
        $('#editImageTitle').keypress(function(e){
            if (e.which == 13) {
                self.editImageTitle();
                return false;
            }
        });
        $(document).click(function(){
            $('#editImageForm:visible').hide();
            self.clearSelected();
        });
        $('#editImageForm').click(function(){
            return false;
        });
        //progress bar position
        $('#progressCont').css('margin-top', (this.thumbHeight-40)+'px');
        //refresh page on forbidden
        $.ajaxSetup({
            'error': function(XMLHttpRequest, textStatus, errorThrown) {
                alert('������!');
                location.reload(true);
            }
        });
    },

    initImage: function(image) {
        $(image).find('img:first').click(this.itemClick.prototypeBind(
            this,
            $(image).find('img:first')[0]
        ));
        $(image).find('a.gallery-delete').click(this.deleteImage.prototypeBind(
            this,
            $(image).find('a.gallery-delete')[0]
        ));
        $(image).find('div.image-title').click(this.showEditImageForm.prototypeBind(
            this,
            $(image).find('div.image-title')
        ));
    
        var self = this;
        $(image).hover(
            function(event) {
                self.lastOveredImageId = this.id;
                $(this).find('a.gallery-delete').show();
                $(this).find('a.gallery-edit').show();
				
                var left = 48;
                var top  = 0;                
                
                $(this).find('a.gallery-zoom').show();
            },
            function (event) {
                if (
                    event.pageX <= $(this).offset().left ||
                    event.pageY <= $(this).offset().top ||
                    event.pageX >= $(this).offset().left + $(this).width() ||
                    event.pageY >= $(this).offset().top + $(this).height()
                ) {
                    $(this).find('a.control').hide();
                }
            }
        );
		
		$('.gallery-edit img', image).each(function(){
			$(this).upload({
				name:  self.formPrefix + "Filedata",
				method: 'post',
				enctype: 'multipart/form-data',
				action: self.baseUrl+'?id='+self.rubricId+'&session_hash='+self.sessionHash,
				params: {
					replace_image: true,
					from_flash: 1,
					item_id: self.getId($(this).closest('div').get(0))
				},
				/*onSubmit: function() {
					$('#progress1').text('Uploading file...');
				},*/
				onComplete: function(data) {
					eval('data = '+data);
					$('#image'+data.id+'>img').attr("src", data.picture_thumb.link );
				}
			});
			$($(this).closest('a')).hide().css('visibility', 'visible');
		});
		
		var sizes = getPageSize();
		$('a.colorbox').colorbox(window.colorBoxParams);
    },

    itemClick: function(img, e) {
		if (Gallery.dragged) {
			Gallery.dragged = false;
			return false;
		}
        var id = this.getId($(img).parent()[0]);
        if (e.ctrlKey || e.metaKey) {
            var index = this.isSelected(id);
            if (index != -1)
                this.deleteSelected(index);
            else
                this.addSelected(id);
        } else if (e.shiftKey) {
            if (this.lastSelected) {
                var items = [];
                var clickedEl = $(img).parent()[0];
                var lastClickedEl = $('#image'+this.lastSelected)[0];
                var clickedIndex = $('div.gallery-image').index(clickedEl);
                var lastClickedIndex = $('div.gallery-image').index(lastClickedEl);
                var fromIndex = Math.min(clickedIndex, lastClickedIndex);
                var toIndex = Math.max(clickedIndex, lastClickedIndex);
                var self = this;
                $('div.gallery-image:eq('+fromIndex+')').add('div.gallery-image:gt('+fromIndex+'):lt('+(toIndex-fromIndex)+')').each(function(){
                    items.push(self.getId(this));
                });
                this.addSelectedAr(items);
            } else {
                this.setSelected(id);
            }
        } else {
            this.setSelected(id);
        }
        return false;
    },

    getId: function(element)
    {
        return element.id.match(/([0-9]+)$/)[0];
    },

    deleteImage: function(deleteBtn)
    {
        var message = this.selectedItems.length > 0 ? '������� ��� ��������� ��������?' : '�������?';
        if (!confirm(message)) return false;
        var items = '';
        if (this.selectedItems.length > 0) {
            items = this.selectedItems.join(',');
        } else {
            items = this.getId(deleteBtn.parentNode);
        }

        $.post(this.baseUrl + '?id=' + this.rubricId, {
            'action': 'delete',
            'items': items
        }, this.deleteItemsCallback.prototypeBind(this, items),'json');
        return false;
    },
	
    deleteItemsCallback : function(items, data) {
            
        if (data.need_approvement !== undefined && data.need_approvement)
        {
            var message = this.selectedItems.length > 0 ? '��������� ������� ������� � ������� ���������. �������?' : '��������� ������ ������ � ������� ���������. �������?';
            if (!confirm(message)) return false;
            $.post(this.baseUrl + '?id=' + this.rubricId, {
                'action': 'delete',
                'approved':'1',
                'items': items
            }, this.deleteItemsCallback.prototypeBind(this, items),'json');
        }
        else if (data.ok)
        {
            if (this.selectedItems.length > 0) {
                for (var i=0; i<this.selectedItems.length; i++)
                    $('#image'+this.selectedItems[i]).remove();
                this.selectedItems[i] = [];
                this.lastSelected = null;
            } else {
                $('#image'+items).remove();
            }
        }
		
    },

    editImageTitle: function() {
        var values = {
            'action': 'edit',
            'title': $('#editImageTitle').attr('value'),
            'id': $('#editImageId').attr('value')
        };
        
        if (values.title!="���������"){
            
            $.post(this.baseUrl, values, function(data) {
                $('#image'+values.id+' div.image-title').text(values.title);
                if (!data.ok) alert("������!");
            },'json');
        }
        $('#editImageForm').hide();
    },

    showEditImageForm: function(title) {
        $('#editImageForm').css('left',$(title).offset().left-15);
        $('#editImageForm').css('top',$(title).offset().top-35);
        $('#editImageTitle').attr('value',$(title).text());
        $('#editImageId').attr('value',this.getId($(title).parent().get(0)));
        $('#editImageForm').show();
        $('#editImageTitle').focus();
        return false;
    },

    uploadUpdateFileCounter: function(stats) {
        var fileUploaded = stats.successful_uploads + stats.upload_errors + stats.upload_cancelled;
        $('#fileCounter').show().text(
            '�������� ������ ' + fileUploaded + '/' + (fileUploaded + stats.files_queued)
            );
    },

    isSelected: function(id) {
        for(var i=0; i<this.selectedItems.length; i++)
            if (this.selectedItems[i] == id) return i;
        return -1;
    },

    addSelected: function(id) {
        if (-1 == this.isSelected(id)) {
            this.selectedItems.push(id);
            $('#image'+id).css('opacity','0.4');
            this.lastSelected = id;
        }
    },

    setSelected: function(id) {
        this.clearSelected();
        this.addSelected(id);
    },

    addSelectedAr: function(ids) {
        for(var i=0; i<ids.length; i++) {
            this.addSelected(ids[i]);
        }
    },

    deleteSelected: function(index) {
        var id = this.selectedItems[index];
        $('#image'+id).css('opacity','1');
        if (this.lastSelected == id) this.lastSelected = null;
        this.selectedItems.splice(index,1);
    },

    clearSelected: function() {
        var selLength = this.selectedItems.length;
        for(var i=0; i<selLength; i++) {
            this.deleteSelected(0);
        }
    },

    hideFormButtons: function() {
        $(".cms-save-but, .cms-delete-but").hide();
    },

    showFormButtons: function() {
        $(".cms-save-but, .cms-delete-but").show();
    }
};

var imagesUploadSettings = {
    file_size_limit:  "8 MB",
    file_types_description: "���������� ���� ������",

    post_params: {
        'swfupload_user_agent': navigator.userAgent || navigator.vendor || window.opera,
        'from_flash' : '1'
    },

    button_placeholder_id : "spanButtonPlaceholder",
    button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
    button_cursor: SWFUpload.CURSOR.HAND,

    file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {
        $('#SWFUpload_0').blur();
        if (numFilesSelected > 0) this.startUpload();
    },
    upload_start_handler: function() {
        this.customSettings.gallery.hideFormButtons();

        this.customSettings.gallery.uploadUpdateFileCounter(this.getStats());
        $('#progressCont').show();
        $('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/ajax-loader-arrows.gif)');
        return true
    },
    upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
        $('#progressBar').css(
            'width',
            Math.round(($('#progressCont').width()-2)*(bytesLoaded / bytesTotal))
            );
    },
    upload_error_handler: function(file, errorCode, message) {
        alert('������!');
        location.reload(true);
    },
    upload_success_handler: function(file, data) {

        try
        {
            eval('data = ' + data + ';');
        }
        catch (err)
        {
            data = {};
        }

        if (data.ok) {
            
           
            //$("#gallery div.gallery-image:first").before(data.html);
            $("#gallery").children(":first").after(data.html);
            //console.log($('#gallery div.gallery-image').get(0));
            this.customSettings.gallery.initImage($('#gallery div.gallery-image').get(0));
            
            //$('#addImageButton').before(data.html);
            //$('#gallery div.gallery-image:last .image-title').html('���������');
            //this.customSettings.gallery.initImage($('#gallery div.gallery-image').get().reverse()[0]);
        } else {
            location.reload(true);
        }
    },
    upload_complete_handler: function() {
        this.customSettings.gallery.showFormButtons();

        var stats = this.getStats();
        this.customSettings.gallery.uploadUpdateFileCounter(stats);
        if (stats.files_queued === 0) {
            $('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/add.png)');
            $('#fileCounter').text('').hide();
            $('#progressCont').hide();
            stats.successful_uploads = stats.upload_errors = stats.upload_cancelled = 0;
            this.setStats(stats);
        } else {
            this.startUpload();
        }
    }
};

var imageOneUploadSettings = {
    file_size_limit:  "8 MB",
    file_types_description: "���������� ���� ������",

    post_params: {
        'swfupload_user_agent': navigator.userAgent || navigator.vendor || window.opera,
        'replace_image': true,
        'from_flash' : '1'
    },

    button_placeholder_id : "spanReplaceButtonPlaceholder",
    button_width: 32,
    button_height: 32,
    button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
    button_cursor: SWFUpload.CURSOR.HAND,
    button_action: SWFUpload.BUTTON_ACTION.SELECT_FILE,

    file_dialog_start_handler: function() {
        this.customSettings.gallery.editedItemId = this.customSettings.gallery.lastOveredImageId;
    },
    file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {
        if (numFilesSelected > 0) {
            this.customSettings.gallery.swfUploadOne.removePostParam('item_id');
            this.customSettings.gallery.swfUploadOne.addPostParam(
                'item_id',
                this.customSettings.gallery.getId($('#'+this.customSettings.gallery.editedItemId).get(0))
                );
            this.startUpload();
        }
    },
    upload_start_handler: function() {
        this.customSettings.gallery.hideFormButtons();

        this.customSettings.gallery.uploadUpdateFileCounter(this.getStats());
        $('#progressCont').show();
        $('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/ajax-loader-arrows.gif)');
        return true
    },
    upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
        $('#progressBar').css(
            'width',
            Math.round(($('#progressCont').width()-2)*(bytesLoaded / bytesTotal))
            );
    },
    upload_error_handler: function(file, errorCode, message) {
        alert('������!');
        location.reload(true);
    },
    upload_success_handler: function(file, data) {
        try
        {
            eval('data = ' + data + ';');
        }
        catch (err)
        {
            data = {};
        }

        if (data.ok)
        {
            if (data.picture.is_image)
            {
                $('#'+this.customSettings.gallery.editedItemId+' img').get(0).src = data.picture_thumb.link;
            }
            else
            {
                $('#'+this.customSettings.gallery.editedItemId+' img').get(0).src = base_url + 'skins/images/file_icons/'+data.picture.ext+'.gif';
            }

            this.customSettings.gallery.initImage($('#gallery div.gallery-image').get().reverse()[0]);
        }
        else
        {
            location.reload(true);
        }
    },
    upload_complete_handler: function() {
        this.customSettings.gallery.showFormButtons();

        var stats = this.getStats();
        this.customSettings.gallery.uploadUpdateFileCounter(stats);
        if (stats.files_queued === 0) {
            $('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/add.png)');
            $('#fileCounter').text('').hide();
            $('#progressCont').hide();
            stats.successful_uploads = stats.upload_errors = stats.upload_cancelled = 0;
            this.setStats(stats);
        } else {
            this.startUpload();
        }
    }
};
