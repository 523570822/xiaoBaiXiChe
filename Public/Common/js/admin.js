function delFile(id, file_id) {
    doPost('/index.php/Manager/Index/delFile', {id: file_id}, function(res) {
        if(res.code == 1) {
            $('#'+id+'_pre').remove();
        }else {
            showMsg(0, res.message);
        }
    });
}
/**
 * 选中图片处理事件
 * @param obj
 * @returns {boolean}
 */
function chooseFile(showFile, obj, files) {
    if(!showFile) return false;
    var id = $(obj).attr('id');
    if(showFile == 'true') {
        if(files) {
            if($(obj).parent().parent().find('.img-show').length == 0) {
                $(obj).parent().parent().append('<div class="layui-input-block img-show"></div>');
            }
        }else {
            if($(obj).parent().parent().parent().find('.img-show').length == 0) {
                $(obj).parent().parent().parent().append('<div class="layui-input-block img-show"></div>');
            }
        }

        var file = obj.files[0];
        if (file.type.match(/image.*/)) {
            doPost('/index.php/Manager/Index/uploadFile', {file: obj}, function(res) {
                if(res.code == 1) {
                    // 添加input
                    if(files) {
                        $(obj).parent().append('<input type="hidden" name="'+$(obj).data('name')+'" value="'+res.data+'" class="layui-input">');
                    }else {
                        if($('[name="'+$(obj).data('name')+'"]').length == 0) {
                            $(obj).parent().append('<input type="hidden" name="'+$(obj).data('name')+'" value="'+res.data+'" class="layui-input">');
                        }
                        $('[name="'+$(obj).data('name')+'"]').val(res.data);
                    }
                    // 展示图片
                    var reader = new FileReader();
                    reader.onload = function () {
                        if(files) {
                            var imgBox = $(obj).parent().parent().find('.img-show');
                            var html = '';
                            html += '<div class="img-show-item" id="' + id + '_pre">';
                            html += '    <img src="'+reader.result+'"/>';
                            html += '    <i class="layui-icon layui-icon-close close" onclick="delFile(\''+id+'\', \''+res.data+'\');"></i>';
                            html += '</div>';
                            imgBox.append(html);
                        }else {
                            var imgBox = $(obj).parent().parent().parent().find('.img-show');
                            imgBox.html('');
                            var html = '';
                            html += '<div class="img-show-item" id="' + id + '_pre">';
                            html += '    <img src="'+reader.result+'"/>';
                            html += '    <i class="layui-icon layui-icon-close close" onclick="delFile(\''+id+'\', \''+res.data+'\');"></i>';
                            html += '</div>';
                            imgBox.append(html);
                        }
                    };
                    reader.readAsDataURL(file);
                }else {
                    showMsg(res.code, res.message);
                }
            });
        } else {
            showMsg(0, '选中的文件不是图片，无法展示');
        }
    }else if(showFile.indexOf('#') >= 0) {
        doPost('/index.php/Manager/Index/uploadFile', {file: obj}, function(res) {
            if(res.code == 1) {
                // 添加input
                if($('[name="'+$(obj).data('name')+'"]').length == 0) {
                    $(obj).parent().append('<input type="hidden" name="'+$(obj).data('name')+'" value="'+res.data+'" class="layui-input">');
                }
                $('[name="'+$(obj).data('name')+'"]').val(res.data);
                // 展示名字
                if(obj.files[0].name) {
                    $(showFile).text(obj.files[0].name);
                }
            }else {
                showMsg(res.code, res.message);
            }
        });

    }
}
/**
 * 上传图片
 * @param data
 * @returns {boolean}
 * @constructor
 */
function UploadFile(data) {
    // 判断元素是否存在
    if(!data.element || $(data.element).length == 0) {
        console.error(data.element + '不存在'); return false;
    }
    // 单文件上传还是多文件上传
    var uploadFiles = data.files ? true : false;
    // 选中文件后展示
    var showFile = data.showFile ? data.showFile : false;
    // 字段名
    var fieldName = data.fieldName ? data.fieldName : 'uploadFile';
    // 选择文件元素
    var chooseBtn = $(data.element);
    // 文件列表
    var fileList = [];

    // 选择文件点击事件
    chooseBtn.click(function() {
        var file = {
            name: fieldName + (uploadFiles?'[]':''),
            id: 'Upload_' + fieldName + '_' + (fileList.length + 1)
        };

        var file_input = '<input type="file" data-name="'+ file.name +'" id="'+ file.id +'" style="display: none;" onchange="chooseFile(\''+showFile+'\', this, '+ eval(uploadFiles) +');">';
        if(!uploadFiles) { // 单文件上传
            if($('[data-name="'+file.name+'"]').length == 0) {
                $(this).parent().append(file_input);
            }
        }else { // 多文件上传
            $(this).parent().append(file_input);
            fileList.push(file);
        }
        // 开始选择文件
        document.getElementById(file.id).click();
    });
}

/**
 * 跳转Url
 * @param url
 */
function jumpUrl(url) {
    location.href = url;
}
/**
 * 操作多条数据
 * @param url
 * @param action
 * @returns {boolean}
 */
function confirmAllAction(url, action) {
    var ids = [];
    $('[name=ids]:checked').each(function() {
        ids.push($(this).val());
    });
    if(ids.length == 0) {
        showMsg(0, '请至少选择一行');
        return false;
    }
    layer.confirm('是否确认'+action+ids.length+'条选中数据？', {
        btn: ['确认','取消']
    }, function(){
        doPost(url, {ids:ids}, function(res) {
            showMsg(res.code, res.message, function() {
                if(res.code == 1) {
                    location.reload();
                }
            });
        });
    });
}
/**
 * 操作一条数据
 * @param url
 * @param action
 * @param id
 * @returns {boolean}
 */
function confirmOneAction(url, action, id) {
    if(!id) {
        showMsg(0, '错误操作');
        return false;
    }
    layer.confirm('是否确认'+action+'选中数据？', {
        btn: ['确认','取消']
    }, function(){
        doPost(url, {id:id}, function(res) {
            showMsg(res.code, res.message, function() {
                if(res.code == 1) {
                    location.reload();
                }
            });
        });
    });
}
/**
 * 提示消息
 * @param icon
 * @param msg
 * @param func
 */
function showMsg(icon, msg, func) {
    icon = icon == 1 ? 1 : (icon < 0 ? 2 : 0);
    if(!func) func = function() {};
    var data = {
        offset: '100px',
        icon: icon,
        time: 1500
    };
    layer.msg(msg, data);
    setTimeout(func, 1600);
}

/**
 * POST操作
 * @param url
 * @param data
 * @param func
 */
function doPost(url, data, func) {
    if(!func) func = function(res) {};
    if(!data) data = {};
    var formData = new FormData();
    for(var i in data) {
        if(data[i] instanceof Array) {
            for(var o in data[i]) {
                formData.append(i + '[]', data[i][o])
            }
        }else if(data[i].localName && data[i].localName == 'input') {
            formData.append(i, data[i].files[0], data[i].files[0].name)
        }else {
            formData.append(i, data[i]);
        }
    }
    var loading = layer.load(3, {shade: [0.5,'#fff']});
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'JSON',
        async:false,
        contentType : false,
        processData : false,
        success: function(res) {
            layer.close(loading);
            func(res);
        },
        error: function(res) {
            layer.close(loading);
            func(res);
        }
    });
}
/**
 * 删除一条数据
 * @param url
 * @param action
 * @param id
 * @returns {boolean}
 */
function confirmDelAction(url, action, id) {
    if(!id) {
        showMsg(0, '错误操作');
        return false;
    }
    layer.confirm('是否确认'+action+'选中数据？', {
        btn: ['确认','取消']
    }, function(){
        ids = [];
        ids.push(id)
        doPost(url, {ids:ids}, function(res) {
            showMsg(res.code, res.message, function() {
                if(res.code == 1) {
                    location.reload();
                }
            });
        });
    });
}
/**
 * 检查参数是否为空
 * 需要检查的输入项要加 required属性
 * @returns object|false
 */
function checkParam() {
    var data = {};
    var result = true;
    $('.layui-input').each(function() {
        var key = $(this).attr('name');
        if(key != '' && !data[key]) {
            if($(this).attr('type') == 'checkbox') {
                var val = [];
                $('[name="'+key+'"]:checked').each(function() {
                    val.push($(this).val());
                });
            }else if(key.indexOf('[]') >= 0){
                var val = [];
                $('[name="'+key+'"]').each(function() {
                    val.push($(this).val());
                });
            }else if($(this).attr('type') == 'radio') {
                var val = $('[name="'+key+'"]:checked').val();
            } else {
                var val = $('[name="'+key+'"]').val();
            }
            var require = $('[name="'+key+'"]').attr('required');
            if(require !== undefined && (val == '' || val == [])) {
                $('[name='+key+']').focus();
                showMsg(0, '必填参数不能为空');
                result = false;
                return false;
            }
            data[key] = val;
        }
    });
    return result ? data : false;
}
/**
 * 返回历史页面
 */
function goback() {
    self.location = document.referrer;
    //history.go(-1);
    //location.reload();
}
/**
 * Jquery绑定事件
 */
$(document).ready(function() {
    // id 全选/反选
    $('#checkAllId').click(function() {
        if($(this).is(':checked')) {
            $('[name="ids"]').prop('checked', true);
        }else {
            $('[name="ids"]').prop('checked', false);
        }
    });
    // 单元格编辑
    $('td[table-edit="true"]').dblclick(function() {
        var url = $(this).attr('table-edit-url');
        var key = $(this).attr('table-edit-key');
        var val = $(this).text();
        $(this).append('<input type="text" data-url="'+url+'" data-val="'+val+'" class="layui-input layui-table-edit"/>');
        $(this).find('.layui-table-edit').blur(function() {
            var input = $(this);
            var val = input.val();
            var _val = val.replace(/(^\s*)|(\s*$)/g, "");
            if(val != input.data('val')) {
                doPost(input.data('url'), {key: key, value: _val}, function(res) {
                    if(res.code == 1) {
                        input.parent().text(val);
                    }else {
                        input.remove();
                    }
                    showMsg(res.code, res.message, function() {
                        if(res.code == 1) {
                            location.reload();
                        }
                    })
                });
            }else {
                input.remove();
            }
        });
        $(this).find('.layui-table-edit').focus().val(val);
    });
    // tab面板切换
    $('.layui-card-body .layui-tab-title li').click(function() {
        if(!$(this).hasClass('layui-this')) {
            // 切换标题
            $(this).parent().find('li').removeClass('layui-this');
            $(this).addClass('layui-this');
            var index = $(this).index();
            // 切换面板
            $(this).parent().parent().find('.layui-tab-content .layui-tab-item').removeClass('layui-show');
            $(this).parent().parent().find('.layui-tab-content .layui-tab-item').eq(index).addClass('layui-show');
        }
    });
    // 分页跳转
    $('.layui-laypage-btn').click(function() {
        var page = $(this).parent().find('.layui-input').val();
        page = parseInt(page);
        if(page > 0) {
            var url = $(this).data('url');
            location.href = url.replace('{page}', page);
        }else {
            showMsg(0, '请输入正确的页码');
        }
    });
});