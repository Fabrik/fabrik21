/**
 * @fileoverview This demo is used for MarkerClusterer. It will show 100 markers
 * using MarkerClusterer and count the time to show the difference between using
 * MarkerClusterer and without MarkerClusterer.
 * @author Xiaoxi Wu
 */

(function() {
   var map = null;
   var pics = null;
   var markerClusterer = null;
   var markers = [];

   function $(element) {
     return document.getElementById(element);
   }

   var AMC = {
     init: function() {
       if(GBrowserIsCompatible()) {
         map = new GMap2($('map'));
         map.setCenter(new GLatLng(39.91, 116.38), 2);
         map.addControl(new GLargeMapControl());
         pics = data.photos;
         showMarkers(true);
       }
     },
     showMarkers: function(isInit) {
       showMarkers(isInit);
     },
     changeType: function() {
       $("tcounts").innerHTML = "cleaning...";
       setTimeout("AMC.showMarkers()", 0);
     },
     changeNums: function() {
       $("tcounts").innerHTML = "cleaning...";
       $('markerlist').innerHTML = "";
       setTimeout("AMC.showMarkers(true)", 0);
     },
     timing: function() {
       var start = new Date();
       if ($("usegmm").checked) {
         markerClusterer = new MarkerClusterer(map, markers);
       } else {
         for (var i = 0; i < markers.length; i++) {
           map.addOverlay(markers[i]);
         }
       }
       var end = new Date();
       $("tcounts").innerHTML = end - start;
     }
   };

   function showMarkers(init) {
     map.clearOverlays();
     markers = [];
     var icon = new GIcon(G_DEFAULT_ICON);
     icon.image = "http://chart.apis.google.com/chart?cht=mm&chs=24x32&chco=FFFFFF,008CFF,000000&ext=.png";
     var type = 1;

     if($("usegmm").checked) {
       type = 0;
     }

     if(markerClusterer != null) {
       markerClusterer.clearMarkers();
     }

     var panel = document.getElementById('markerlist');
     var lg = $("mcounts").value;
     for (var i = 0; i < lg; i++) {
       var title = pics[i].photo_title;
       if (title == "") {
         title = "No title";
       }
       if (init) {
         var item = document.createElement("div");
         var tit = document.createElement("div");
         tit.innerHTML = title;
         item.style.cssText = 'padding:2px 0;width:265px;border-bottom:1px solid #E0ECFF;cursor:pointer;overflow:hidden;';
         panel.appendChild(item);
         item.appendChild(tit);
         GEvent.addDomListener(item, "mouseover", function() {
                                 this.style.backgroundColor = "#E0ECFF";
                               });
         GEvent.addDomListener(item, "mouseout", function() {
                                 this.style.backgroundColor = "white";
                               });
       }
       var latlng = new GLatLng(pics[i].latitude, pics[i].longitude);
       var marker = new GMarker(latlng, {icon: icon});
       var fn = markerClickFn(pics[i], latlng);
       GEvent.addListener(marker, "click", fn);
       if (init) {
         GEvent.addDomListener(tit, "click", fn);
       }
       markers.push(marker);
     }
     $("tcounts").innerHTML = "timing...";
     setTimeout("AMC.timing()", 0);
   }

   function markerClickFn(pic, latlng) {
     return function() {
       var title = pic.photo_title;
       var url = pic.photo_url;
       var fileurl = pic.photo_file_url;
       var infoHtml = '<div style="width:210px;"><h3>' + title
         + '</h3><div style="width:200px;height:200px;line-height:200px;margin:2px 0;text-align:center;">'
         + '<a id="infoimg" href="' + url + '" target="_blank">Loading...</a></div>'
         + '<a href="http://www.panoramio.com/" target="_blank">'
         + '<img src="http://maps.google.com/intl/en_ALL/mapfiles/iw_panoramio.png"></img></a><br/>'
         + '<a href="' + pic.owner_url + '" target="_blank">' + pic.owner_name + '</a>';
       var img = document.createElement("img");
       GEvent.addDomListener(img, "load", function() {
                               if ($("infoimg") == null) {
                                 return;
                               }
                               img = adjustImage(img, 200, 200);
                               img.style.cssText = "vertical-align:middle;padding:1px;border:1px solid #EAEAEA;";
                               $("infoimg").innerHTML = "";
                               $("infoimg").appendChild(img);
                             });
       img.src = fileurl;
       if(img.readyState == "complete" || img.readyState == "loaded") {
         img = adjustImage(img, 280, 200);
         infoHtml += '<img width=' + img.width + ' height=' + img.height
           + ' style="vertical-align:middle;padding:1px;border:1px solid #aAaAaA"></img>';
       }
       infoHtml += '</div></div>';
       map.openInfoWindowHtml(latlng, infoHtml);
     };
   }
   function adjustImage(img, maxwidth, maxheight) {
     var wid = img.width;
     var hei = img.height;
     var newwid = wid;
     var newhei = hei;
     if(wid / maxwidth > hei / maxheight){
       if(wid > maxwidth){
         newwid = maxwidth;
         newhei = parseInt(hei * newwid / wid);
       }
     } else {
       if(hei > maxheight) {
         newhei = maxheight;
         newwid = parseInt(wid * newhei / hei);
       }
     }
     var src = img.src;
     img = document.createElement("img");
     img.src = src;
     img.width = newwid;
     img.height = newhei;
     return img;
   }
   window.AMC = AMC || window.AMC;
 })();
