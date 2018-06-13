/**
 * [wxuploadimg 微信接口上传，预览图片]
 * @return  {Function} callback [回调函数]
 * 
 *  使用：
 *    // 浏览上传图片
      wxuploadimg(function(res){
          console.log('res: ',res);

          var $span = $("<span></span>");
          var $span1 = $("<span>X</span>");
          var $img = $('<img src="" alt="" />');

          $img[0].width = "100%";
          $img[0].height = "90%";
          $img[0].src = res['src'];
          
          $span1.addClass("delPic");
          $span1.css({zIndex: '999'});
          $span.append($span1);
          $span.append($img[0]);

          // 显示图片
          $('#picShow').append($span);
          // 待发送给后台的图片id
          $('input[name="pic"]').val(res.media_Id);
      });
 */
var backdata = [];    // 需要上传给后台的图片id
var localIdArr = [];
var len;
var wxuploadimg = function(nums, callback){

  takePicture(nums);
  // 获取图片
  function takePicture(nums) {  
   	wx.chooseImage({  
       	count: nums,  
       	needResult: 1,  
       	sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有  
       	sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有  
       	success: function (data) {                  
           	// localIds = data.localIds[0];   // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片        
            // console.log('data.localIds: ',data.localIds);
           	// 多张图片
            len = data.localIds.length;
            localIdArr = data.localIds;
            for(var i=0; i<data.localIds.length; i++){
              backdata.push({});
              (function(localIds, num){
                setTimeout(function(){
                  // console.log('localIds: ',localIds);
                  // console.log('num: ',num);
                  wxuploadImage(localIds,num);
                },0)
              })(data.localIds[i],i)
            }
       	},
       	fail: function (res) {
       		console.log('失败：',JSON.stringify(res));
       	}    
   	});  
   
  } 
  // 上传图片
  function wxuploadImage(localIds,num) {
    console.log('upnum: ',num);
    
    if(window.__wxjs_is_wkwebview){ // IOS

      console.log('localIdArr: ',localIdArr);
      var index = num;
      if(index+1 < localIdArr.length){
        setTimeout(function(){
          num--;
          console.log('index: ',index);
          // wxuploadImage(localIdArr[index], index);
        },100)
        
      }
    }
    wx.uploadImage({
        localId: localIds, // 需要上传的图片的本地ID，由chooseImage接口获得  
        isShowProgressTips: 1, // 默认为1，显示进度提示  
        success: function (res) {
            console.log('_num: ', num);
            // console.log('backdata[num]: ', backdata[num]);
            backdata[num]['media_Id'] = res.serverId;
            wxgetLocalImgData(localIds,num);
            
        },
        fail: function (error) {
            picPath = '';  
            localIds = '';  
        }
    });
    
  }
  // 下载图片(本地显示)
  function wxgetLocalImgData(localIds,num){
      console.log('wx.getLocalImgData_localIds: ',localIds);
      console.log('num: ',num);

      if(window.__wxjs_is_wkwebview){   // IOS
    	    wx.getLocalImgData({
    	        localId: localIds, // 图片的localID
    	        success: function (res) {
                console.log('wx.getLocalImgData_res: ', res);                  
                var localData = res.localData; // localData是图片的base64数据，可以用img标签显示
                // localData = localData.replace('jgp', 'jpeg');//iOS 系统里面得到的数据，类型为 image/jgp,因此需要替换一下                                            
    			      
                console.log('wx.getLocalImgData_num: ',num);
                console.log('backdata[num]: ',backdata[num]);
                backdata[num]['src'] = localData;
                // if(num == 0){
                  // 回调
                  callback(backdata);
                  // backdata = [];
                // }
    	        },
    	        fail:function(res){
    	          	alert("显示失败");
    	        }
    	    })

      }else{
        backdata[num]['src'] = localIds;
          
        if(num+1 == backdata.length){
          // 回调
          callback(backdata);
          backdata = [];
        }
      }
  	
  }
}