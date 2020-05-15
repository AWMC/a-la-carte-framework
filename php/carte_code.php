<?php  
<?xml version="1.0" encoding="UTF-8"?>
<!-- 2012 Ancient World Mapping Center. Some rights reserved -->
<!-- Author(s): Ryan Horne -->
<!-- Version: 2.0 25 September 2012 -->

<html>
<head>
<link rel="shortcut icon" href="../images/favicon.ico" />
<title>AWMC: À-la-carte Map</title>
   <script src="http://openlayers.org/api/OpenLayers.js"></script>
   <script src="/includes/extjs/3.4.0/adapter/ext/ext-base.js" type="text/javascript"></script>
<script src="/includes/extjs/3.4.0/ext-all.js"  type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="/includes/extjs/3.4.0/resources/css/ext-all.css"></link>
<script src="/includes/geoext/1.1/lib/GeoExt.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="/includes/geoext/1.1/resources/css/geoext-all-debug.css"></link>
<script type="text/javascript" src="../extensions/Exporter-all.js"></script>
<!-- All of the rules for the various feature types are in the file below -->
<script type="text/javascript" src="feature_rules.js"></script>


<script type="text/javascript">

//to do:
//figure out the printer thing
//make sure that custom features do not generate a flikr tab. Perhaps take the whole explore further tab out for negative pids

//global here
var mapPanel, tree;
var layers;
var map;
var ctrl, toolbarItems2 = [], toolbarItems = [], bbarItems = [], action, actions = {};
//this is the extent for the map
var extent = new OpenLayers.Bounds(-12.200000, 11.000000, 80.000000, 83.000000);
//these are the urls for our various map services. These need to be changed when we move the server to a different environement. 
var baseUrl = '/cgi-bin/mapserv?map=/sapientia/www/html/projects/awmc/mapfiles/carte_wms2.map';
var overUrl = '/cgi-bin/mapserv?map=/sapientia/www/html/projects/awmc/mapfiles/over.map';
//for now sets the projectipon for everything. We can change this if we are going to serve google layers or something like that
var mapProjection  = new OpenLayers.Projection("EPSG:4326");
//this is to access the pelagios API for our feature info
var pelagApi = 'http://pelagios.dme.ait.ac.at/api/places/http%3A%2F%2Fpleiades.stoa.org%2Fplaces%2F'; 
// and the flickr url
var flickrUrl = 'http://www.flickr.com/photos/tags/pleiades:*=';
//and pleiades 
var pleaBase = 'http://pleiades.stoa.org/places/';
var currentFeature = new OpenLayers.Feature.Vector(); 
//this may be buried somewhere in the documentation 
var currentFeatureType;
var popupToggle = 1;


   var permalinkProvider = new GeoExt.state.PermalinkProvider();
    Ext.state.Manager.setProvider(permalinkProvider);

//this is the options for the overlay layers. The base layer and vector layers require their own initialization
var overOptions = {
	maxExtent:  extent,
	maxResolution: "auto",
	displayInLayerSwitcher: true,
	group: "physical",
	isBaseLayer: false,
    projection: mapProjection,
    tileSize: new OpenLayers.Size(200,100)
	};

 
//map options

var options ={
	projection: mapProjection,
	maxExtent: extent,
	minScale: 50000000,
	maxResolution: "auto" 
	};
var fakePID = -9999;


//the "big" function that kicks off the application
Ext.onReady(function() 
{

var featPop = new Ext.Window();

var featcounter = 0;

var popup = new Ext.Window();
var geoJsonResponse;
var geojson_format = new OpenLayers.Format.GeoJSON();


var win = new Array();
var counter = 0;

//we want our tools to look differently than normal or default things - mostly for visibility
        var defStyle = {strokeColor: "blue", strokeOpacity: "0.7", strokeWidth: 2, fillColor: "blue", pointRadius: 3, cursor: "pointer"};
        var sty = OpenLayers.Util.applyDefaults(defStyle, OpenLayers.Feature.Vector.style["default"]);


var popcounter = 0;

var timeArry = [];
var toggleGroup = "language controls";
var rulesDef = [];

//the fields we are going to be passing around from the postgis data
var fieldDefs =[
			{name: 'searchrename', type: 'string'},
            {name: 'en_name', type: 'string'},
            {name: 'gr_name', type: 'string'},
            {name: 'la_name', type: 'string'},
            {name: 'pid', type: 'string'},
            {name: 'featuretyp', type: 'string'},
            {name: 'timeperiod', type: 'string'},
            {name: 'perseus_li', type: 'string'},
            {name: 'wiki_link', type: 'string'},            
            {name: 'path', type: 'string'},
            {name: 'map_num', type: 'string'},
            {name: 'popup_status', type: 'string'}

            ];
            
            //there is a seriously big mess up with the proxy server so we have to force these things inline

var printCapabilities = 
{"scales":[{"name":"1:25,000","value":"25000"},{"name":"1:50,000","value":"50000"},{"name":"1:100,000","value":"100000"},{"name":"1:200,000","value":"200000"},{"name":"1:500,000","value":"500000"},{"name":"1:1,000,000","value":"1000000"},{"name":"1:2,000,000","value":"2000000"},{"name":"1:4,000,000","value":"4000000"},{"name":"1:8,000,000","value":"8000000"},{"name":"1:16,000,000","value":"16000000"},{"name":"1:32,000,000","value":"32000000"},{"name":"1:64,000,000","value":"64000000"},{"name":"1:128,000,000","value":"128000000"},{"name":"1:256,000,000","value":"256000000"}],"dpis":[{"name":"56","value":"56"},{"name":"72","value":"72"},{"name":"127","value":"127"},{"name":"190","value":"190"},{"name":"254","value":"254"}],"outputFormats":[{"name":"pdf"}],"layouts":[{"name":"A4 portrait","map":{"width":440,"height":483},"rotation":true},{"name":"Complex layout","map":{"width":440,"height":503},"rotation":true}],"printURL":"http://sapientia.its.unc.edu/mapfish/pdf/print.pdf","createURL":"http://sapientia.its.unc.edu/mapfish/pdf/create.json"};

//functions go here



function makeMapNumbers(layerIn){


        var nummer = 1;
        for(var i = 0; i < layerIn.features.length; i++)
        {

        if ( layerIn.features[i].renderIntent != "hidden")
        {
        layerIn.features[i].attributes.map_num = nummer;
                nummer++;
        }
        else {
                layerIn.features[i].attributes.map_num ='';
}
        }
     //   vectorstore.loadData(layerIn.features, false);

        


}




function formatLonlats(lonLat) {
        var lat = lonLat.lat;
        var long = lonLat.lon;
        var ns = OpenLayers.Util.getFormattedLonLat(lat);
        var ew = OpenLayers.Util.getFormattedLonLat(long,'lon');
        return ns + ', ' + ew + ' (' + (Math.round(lat * 10000) / 10000) + ', ' + (Math.round(long * 10000) / 10000) + ')';
    }



  //this is for the styling and context
  //first it will set all the title atributes for each feature to none.
  //next it will check for a key value (english, greek, latin, none) everytime the button is pressed
  //then it will use that key value to assign the label which will be unassigned by default
 //the context and template will be used below in the cities layer. It had to be done this way so the language rules 
 //would not show up in the geoext legend

   
   var contextEn = {
   foo: function(feature) {
   return feature.attributes.en_name;
   }
   };
   
    var contextGr = {
   foo: function(feature) {
   return feature.attributes.gr_name;
   }
   };
   
    var contextLa = {
   foo: function(feature) {
   return feature.attributes.la_name;
   }
   };
   
   var contextMa = {
   foo: function(feature) {
   return feature.attributes.map_num;
   }
   };
   
    var contextNo = {
   foo: function(feature) {
   return "";
   }
   };
   
   //our default rule set
   var template ={
   //this is the label- must be changed to a different variable for different languages
   label : "${foo}",
   fontColor: "black",
   fontSize: "10px",
   fontFamily: "Arial",
   fontWeight: "bold",
   labelAlign: "center",
   labelXOffset : 0,
   labelYOffset : 10,
   labelSelect : true
   };

   var templateHide ={   
   fillOpacity: 0.0,
   strokeOpacity: 0.0
   };
	
	
//end of rules

//we have separate rules stores for purposes of displaying the legend- there are rules that are
//effectively worthless to display (i.e. have a point of 0 or are just text based rues without a marker) 

//we will also use the rules object to fill the dropdown for the feature editor
//if something is not in the rules it can not be added by the user




//we wait here just to make sure the rules went through ok and we do not have cruft in the drop downs
for (var i = 0; i < rules.length; i++)
{
	rulesDef.push(rules[i].filter.value)
}
//so we do not have to worry about alphabetizing while coding
rulesDef.sort();
//this double array gets the features ready for a store below
for ( var i = 0, c = rulesDef.length; i < c; i++ ) {
    rulesDef[i] = [rulesDef[i]];
}

//these are the rules that we want to display in the legend
//so we do not have to worry about alphabetizing while coding
legendRules.sort();

var addreader = new GeoExt.data.FeatureReader({},fieldDefs);

var addstore = new GeoExt.data.FeatureStore({
    reader: addreader,
    fields: fieldDefs,
    autoLoad: false
});

var editreader = new GeoExt.data.FeatureReader({},fieldDefs);

var editstore = new GeoExt.data.FeatureStore({
    reader: addreader,
    fields: fieldDefs,
    autoLoad: false
});


function fnMesShow(measResult){  

var measPanel = new Ext.Panel({
    id: 'measPanel',
    region: 'center',
    bodyStyle: 'background-color:#FFFFFF;',
       autoScroll:true,
       html: "The measurement is: <br>"  
        }); 
	        
var winMeasResult = new Ext.Window({
	id: 'winMeasResult',
	width: 300,
	height: 200,
	resizable: true,
	maximizable: true,
	title: 'Measurement Result',
	layout: 'border',
	items: measPanel,
	closeAction:'hide' //otherwise closing the top will nerf the window
	});

  winMeasResult.addButton({
      	  text: "Close",
       handler:     function() {
       winMeasResult.hide();}

  
  }); 
  
  winMeasResult.show();   
	        
}	   



function fn_editsubmitForm(button,event){
//clear the store and the visual display
editstore.removeAll();


	        	    var geoJSON = new OpenLayers.Format.GeoJSON();
var geoJSONText = geoJSON.write(currentFeature.geometry);


   var f = Ext.getCmp('formPanelEdit');
   var ennameparam = formPanelEdit.getForm().findField("editformenName").getValue();
   var grnameparam = formPanelEdit.getForm().findField("editformgrName").getValue();
   var lanameparam = formPanelEdit.getForm().findField("editformlaName").getValue();
   var typeparam = formPanelEdit.getForm().findField("editformType").getValue();
   var timeparam = formPanelEdit.getForm().findField("editformTime").getValue();
   var pidparam = formPanelEdit.getForm().findField("editformPid").getValue();
   var persparam = formPanelEdit.getForm().findField("editformPers").getValue();
   var wikiparam = formPanelEdit.getForm().findField("editformWiki").getValue();
   var pathparam = formPanelEdit.getForm().findField("editformPath").getValue();
   var searchnameparam = formPanelEdit.getForm().findField("editformSearchName").getValue();




   if( f.getForm().isValid() == true)
   {
       Ext.Ajax.request({

    url : 'geojsonmake.php',
    method: 'GET',
    params : {
        enname : ennameparam,
        grname : grnameparam,
        laname : lanameparam,
        type : typeparam,
        timeperiod: timeparam,
        pid: pidparam,
        geom: geoJSONText
    },
    //if everything works add the features to a store that the user can choose from
    success: function(objServerResponse){
    editstore.loadData(geojson_format.read(objServerResponse.responseText));
       var rows = editstore.getCount( );
       for (var i = 0; i < rows; i++)
       {
       var featadd = editstore.getAt(i);
       var feature = featadd.get("feature");
       feature.attributes.perseus_li = persparam;
              feature.attributes.wiki_link = wikiparam;
              feature.attributes.path = pathparam;
              feature.attributes.searchrename = searchnameparam; 
       var searchpid = feature.attributes.pid;
       var delfeat = cities.getFeaturesByAttribute('pid', searchpid); 
       cities.removeFeatures([delfeat[0]]);
            cities.addFeatures([feature]);
             
        } 
    
    },
    failure : function(objServerResponse){ 
    }
});}
//hide the popup
popup.hide();
}//end
          
          
var formPanelEdit = new Ext.FormPanel({
    autoScroll: true,
    store: editstore,
    height: 350,
//    title: "Edit Attributes",
    buttonAlign :'left',
    id: "formPanelEdit",
    items: [{
        xtype: "textfield",
        name: "editformenName",
        fieldLabel: "English Name",
        id: "editformenName"
    },
    {
        xtype: "textfield",
        name: "editformgrName",
        fieldLabel: "Greek Name",
        id: "editformgrName"
    },
    {
        xtype: "textfield",
        name: "editformlaName",
        fieldLabel: "Latin Name",
        id: "editformlaName"
    },
    {
        xtype: "combo",
        name: "editformType",
        fieldLabel: "Type",
            forceSelection: true, 
        id: "editformType",
        store: new Ext.data.SimpleStore({
        fields: ['rules'],
        data : rulesDef 
    }),
    displayField: 'rules',
	typeAhead: false,
	mode: 'local',
	width:147,
	listWidth: 147,
	allowBlank: false //otherwise nothing will display as there are no rules
    },
       {
        xtype: "textfield",
        name: "editformTime",
        fieldLabel: "Time Period",
        id: "editformTime",
        value: "ACHRL",
        allowBlank: false 
    },
         {
        xtype: "textfield",
        name: "editlat",
        id: "editlat",
        fieldLabel: "Latitude"
        },
         {
        xtype: "textfield",
        name: "editlong",
        id: "editlong",
                fieldLabel: "Longitude"
        },
              {
        xtype: "textfield",
        name: "editformPid",
        id: "editformPid",
        disabled: true,
        readOnly: true,
                fieldLabel: "PID"
        },
        
              {
        xtype: "textfield",
        name: "editformPers",
        id: "editformPers",
                fieldLabel: "Perseus Link"
        },
        
                     {
        xtype: "textfield",
        name: "editformWiki",
        id: "editformWiki",
                fieldLabel: "Wiki Link"
        },
    	{
        xtype: "hidden",
        name: "editformPath",
        id: "editformPath",
        disabled: true,
        readOnly: true,
    	fieldLabel: "Path"
        },
        {
        xtype: "textfield",
        name: "editformSearchName",
        id: "editformSearchName",
        readOnly: true,
        fieldLabel: "Search Result Name"
        }
        ],   
        keys: [
    { key: [Ext.EventObject.ENTER], handler: fn_editsubmitForm }]
});

       



function fn_addsubmitForm(button,event){
//clear the store and the visual display
addstore.removeAll();

var geoJSON = new OpenLayers.Format.GeoJSON();
var geoJSONText = geoJSON.write(currentFeature.geometry);

   var f = Ext.getCmp('formPanelAdd');
   var ennameparam = formPanelAdd.getForm().findField("addformenName").getValue();
   var grnameparam = formPanelAdd.getForm().findField("addformgrName").getValue();
   var lanameparam = formPanelAdd.getForm().findField("addformlaName").getValue();
   var typeparam = formPanelAdd.getForm().findField("addformType").getValue();
   var timeparam = formPanelAdd.getForm().findField("addformTime").getValue();
      var pidparam = fakePID;


   if( f.getForm().isValid() == true)
   {
       Ext.Ajax.request({

    url : 'geojsonmake.php',
    method: 'GET',
    params : {
        enname : ennameparam,
        grname : grnameparam,
        laname : lanameparam,
        type : typeparam,
        timeperiod: timeparam,
        pid: pidparam,
        geom: geoJSONText
    },
    //if everything works add the features to a store that the user can choose from
    success: function(objServerResponse){
    addstore.loadData(geojson_format.read(objServerResponse.responseText));
       var rows = addstore.getCount( );
       for (var i = 0; i < rows; i++)
       {
       var featadd = addstore.getAt(i);
       var feature = featadd.get("feature");
            cities.addFeatures([feature]);
             
        } 
    
    win.hide();
    },
    failure : function(objServerResponse){ 
    }
});}}
          
          
var formPanelAdd = new Ext.FormPanel({
    height: 200,
    width: 200,
    autoScroll: true,
    store: addstore,
    buttonAlign :'left',
    id: "formPanelAdd",
    items: [{
        xtype: "textfield",
        name: "addformenName",
        fieldLabel: "English Name",
        id: "addformenName"
    },
    {
        xtype: "textfield",
        name: "addformgrName",
        fieldLabel: "Greek Name",
        id: "addformgrName"
    },
    {
        xtype: "textfield",
        name: "addformlaName",
        fieldLabel: "Latin Name",
        id: "addformlaName"
    },
    {
        xtype: "combo",
        name: "addformType",
        fieldLabel: "Type",
            forceSelection: true, 
        id: "addformType",
        store: new Ext.data.SimpleStore({
        fields: ['rules'],
        data : rulesDef 
    }),
    displayField: 'rules',
		        typeAhead: true,
		        mode: 'local',
		        width:147,
		        listWidth: 147,
		        allowBlank: false//otherwise nothing will display as there are no rules
    },
       {
        xtype: "textfield",
        name: "addformTime",
        fieldLabel: "Time Period",
        id: "addformTime",
        value: "ACHRL",
        allowBlank: false 
    },
         {
        xtype: "textfield",
        name: "addlat",
        id: "addlat",
                fieldLabel: "Latitude"
        },
         {
        xtype: "textfield",
        name: "addlong",
        id: "addlong",
                fieldLabel: "Longitude"
        }
    ],   keys: [
    { key: [Ext.EventObject.ENTER], handler: fn_addsubmitForm }]
});


formPanelAdd.addButton({
    text: "Cancel",
    handler: function(){win.hide()},
    scope: formPanel
});

formPanelAdd.addButton({
    text: "Ok",
    handler: fn_addsubmitForm,
    scope: formPanel
});          

	        win= new Ext.Window({
	                  id: counter,
 width: 300
, height: 300
, minWidth: 350
, minHeight: 200
, plain: true
, title: 'Set Attributes For New Feature'
, layout: 'fit'
, border: false
, closable: false
, items: formPanelAdd
});


//we have to post, otherwise there is a 111 page URL for ~400 features
// The printProvider that connects us to the print service
var printProvider = new GeoExt.data.PrintProvider({
        method: "POST", // "POST" recommended for production use
        capabilities: printCapabilities, // from the info.json script in the html
        customParams: 
        {
        dpi: 254,
        mapTitle: "First map",
        comment: ""
        }
    });
    // Our print page. Tells the PrintProvider about the scale and center of
    // our page.
    
printPage = new GeoExt.data.PrintPage({
        printProvider: printProvider
    });


//this function sets up our time displays

function timeObj (timeL, dispT)
{
this.timeL = timeL;
this.dispT = dispT;
}
//the times we are using, THis might move to a definition array as we add new objects
//right now Classical time will be the default
timeA = new timeObj ("A", true);
timeC = new timeObj ("C", true);
timeH = new timeObj ("H", true);
timeR = new timeObj ("R", true);
timeL = new timeObj ("L", true);

var timeContainer = [timeA, timeC,timeH, timeR, timeL];


//this is an ugly, UGLY, TERRIBLE kludge to get the date function working
//this is not practical on larger maps, so we will have to do this via the database

//need to add a while loop here to elminate uncecessary iterations
function timeChanger(layer, timeCount)
{
var timeCheck = [];
var isInTime;
	for (var z = 0; z < timeCount.length; z++)
{
		if (timeCount[z].dispT == true)
		timeCheck.push(timeCount[z].timeL)
}


for (var i = 0; i < layer.features.length; i++)
{
isInTime = 0;
for (var f = 0; f < timeCheck.length; f++)
{

			if ( layer.features[i].attributes.timeperiod.indexOf(timeCheck[f]) == -1 && isInTime != 1)
			{
			 layer.features[i].renderIntent = "hidden";
			 layer.features[i].style = OpenLayers.Feature.Vector.style["delete"];
			 layer.drawFeature(cities.features[i]);
			 }
			 else
			 {
			 layer.features[i].style = OpenLayers.Feature.Vector.style[template];
			 layer.features[i].renderIntent = "default";
			 layer.drawFeature(cities.features[i]);
			 isInTime = 1;
			 }
			 }			
}
//just to make sure everythign is caught
layer.redraw();
}


//this function changes the 'dummy' data field to enable us to change languages in our display
//now to redraw the layer with the correct attributes

function nameChanger(nLayer, lang)
{
//changing the context is probably the best way to change all the labels
nLayer.styleMap.styles['default'].context = lang;
nLayer.redraw();
}

//anything that needs to be established before the layers should go here
  
  var featureStyles = new OpenLayers.StyleMap({
       "default": OpenLayers.Feature.Vector.style["default"],
       "select": OpenLayers.Feature.Vector.style.select,
       "hidden":{
               fillOpacity: 0.0,
               strokeOpacity: 0.0
      }
 });
  
 
//now to create the layers indivudally
map = new OpenLayers.Map('map', options);


//now to create the layers indivudally
//the coast is our base layer and as such has different options
var coast = new OpenLayers.Layer.WMS( "Coast Outline", 
                    baseUrl,
                    {layers: "coast_m",transparent: 'true'},
                    {maxExtent:  extent,
                    maxResolution: "auto",
                    isBaseLayer:true,
                    displayInLayerSwitcher: false,
                    visibility: true,
                        tileSize: new OpenLayers.Size(200,100)
                   });
//all of the overlay layers with the exception of the cities require the same options.

var elevation = new OpenLayers.Layer.WMS("Elevation Data", 
                    baseUrl,
                    {layers: "background_el",transparent: 'true'},
                    overOptions
                    );
                    
var hillshade = new OpenLayers.Layer.WMS("Hillshade Data", 
                    baseUrl,
                    {layers: "background_hill",transparent: 'true'},
                    overOptions
                    );
                    
var waterc = new OpenLayers.Layer.WMS( "Water Courses", 
                    baseUrl,
                    {layers: "watercourses_m",transparent: 'true'},
                    overOptions
                	);
                	
var inlandw = new OpenLayers.Layer.WMS( "Inland Water", 
                    baseUrl,
                    {layers: "inlandwater_m",transparent: 'true'},
                    overOptions
                   );
                   
var openw = new OpenLayers.Layer.WMS( "Open Water", 
                    baseUrl,
                      {layers: "openwater_m",transparent: 'true'},
                    overOptions
                    );
                    
var roadsw = new OpenLayers.Layer.WMS("Roads", 
                    baseUrl,
                    {layers: "roads_m",transparent: 'true'},
                    overOptions
                    );
                    
                    roadsw.setVisibility(false);
                    
var urbw = new OpenLayers.Layer.WMS("Urban", 
                    baseUrl,
                    {layers: "urban_m",transparent: 'true'},
                    overOptions
                    );
                    
                    roadsw.setVisibility(false);





                    
var aquew = new OpenLayers.Layer.WMS("Aqueducts", 
                    baseUrl,
                    {layers: "aqua_m",transparent: 'true'},
                    overOptions
                    );
                    
                	aquew.setVisibility(false);

var pAllLayers = new OpenLayers.Layer.WMS("All Pleiades Features", 
                    baseUrl,
                    {layers: "all_m",transparent: 'true'},
                    overOptions
                    );
                    
                	pAllLayers.setVisibility(false);




//this may be ugly, but it is useful for names
var names_l = new OpenLayers.Layer.Vector(
                 "Names",{
                 projection: mapProjection,
                  });


//this is also ugly, but this will hold temporary features for the wms getinfo so they do not automatically add to the features layer

 var citiesTemp = new OpenLayers.Layer.Vector(
                 "Features",{
                 projection: mapProjection
                  });



//the database. The feature NS works but we are not sure why....
 
 var cities = new OpenLayers.Layer.Vector(
                 "Features",{
                 displayInLayerSwitcher: true,
                 group: "culture",
                 projection: mapProjection,
                 //we want to default to no labels
                 styleMap: new OpenLayers.StyleMap(new OpenLayers.Style(template,{context: contextEn, rules: rules}))
                  });
  
       cities.events.on({
    "featureadded": function(evt) { 
            feature = evt.feature;
            currentFeature = feature;
             if(currentFeatureType == 'point')
	        	     {
                 }
feature.attributes.name = feature.attributes.searchrename;
feature.attributes.description = "PID: " + feature.attributes.pid;
                 },
        "featureselected": function(e) {
        popup.hide();
        //this prevents the synced grid from creating a popup. De-syncing and re-syncing the grid caused a lot of problems
        if (popupToggle == 1){
            createPopup(e.feature, cities)};
        },
        "featureunselected": function(){popup.hide();}
    });

//put these into a function so they are created and refreshed each time




	var  vectorstore = new GeoExt.data.FeatureStore({
	title: 'Features',
	layer: cities,
	fields:fieldDefs,
	autoLoad: false
	});



function fnCreateGrid(){

//we do not want popups on this grid
       popupToggle = 0;




/*	
	var gridCounter = String(vectorstore.getCount());
                
     var myTextItemGrid = new Ext.Toolbar.TextItem({text: gridCounter});
       
             	bbar:[ {xtype: 'tbfill'},
    	myTextItemGrid],  */
                      
                var  vgridPanelBase = new Ext.grid.GridPanel({
      //  title: "Features",
        region: 'center',
        id: 'vgridPanelBase',
        autoScroll: true,
        buttonAlign :'left',
        store: vectorstore,
        width: 880,
        height: 130,
        columns: [{
            header: "Name",
            width: 100,
            dataIndex: "en_name",
            sortable: true
            }, 
            {
            header: "Search Name",
            width: 100,
            dataIndex: "searchrename",
            sortable: true
            }, 
             {
            header: "Greek Name",
            width: 100,
            dataIndex: "gr_name",
            sortable: true
            }, 
            {
            header: "Latin Name",
            width: 100,
            dataIndex: "la_name",
            sortable: true
            }, 
             {
            header: "Type",
            width: 100,
            dataIndex: "featuretyp",
            sortable: true
            },
            {
            header: "Time Period",
            width: 100,
            dataIndex: "timeperiod",
            sortable: true
            },
             {
            header: "Pleiades ID",
            width: 100,
            dataIndex: "pid",
            sortable: true
            },
             {
            header: "Map Number",
            width: 100,
            dataIndex: "map_num",
            sortable: true
            }
            ],
         sm: new GeoExt.grid.FeatureSelectionModel()
        });
                   
                        //toolbar items for the feature grid. The show / hide feature is excluded since the code is easier
 //if it is in that section
 //toggle as there really should never be multiple languages at once
 //the timecontainer / timearray should perhaps be cleaned up a bit. Maybe using object properties
 //get the inital separation in there
 
 
 
vgridPanelBase.addButton({
    	  text: "Hide / Show Feature",
    	  handler: function() {
    	  vgridPanelBase.getSelectionModel().each(function(rec) {
            var feature = rec.getFeature();
            if (feature.renderIntent == "hidden")
            {
			feature.style = OpenLayers.Feature.Vector.style[template];
			feature.renderIntent = "default";
			}
            else
            {
            feature.renderIntent = "hidden";
			feature.style = OpenLayers.Feature.Vector.style["delete"];
            }
            cities.drawFeature(feature);
        })
        }
        });
 

  
  
  
  
 
vgridPanelBase.addButton({
    text: 'Hide All Features',
    handler: function(toggled){
        if (toggled) {
        
        for (var i = 0; i < cities.features.length; i++)
        {
        cities.features[i].renderIntent = "hidden";
        cities.features[i].style = OpenLayers.Feature.Vector.style["delete"];
        cities.drawFeature(cities.features[i]);
        }
          for (var i = 0; i < timeArry.length; i++)
        {
        timeArry[i].setChecked(false);
        timeContainer[i].dispT = false;
        }
        } //end of toggled 
        else {
            length.deactivate();
        }
    }
});
  
        
vgridPanelBase.addButton({
    text: 'Show All Features',
    handler: function(toggled){
        if (toggled) {
        
        for (var i = 0; i < cities.features.length; i++)
        {
        cities.features[i].style = OpenLayers.Feature.Vector.style[template];
        cities.features[i].renderIntent = "default";
        cities.drawFeature(cities.features[i]);
        }
        for (var i = 0; i < timeArry.length; i++)
        {
        timeArry[i].setChecked(true);
		timeContainer[i].dispT = true;
        }
        } else {
            length.deactivate();
        }
    }
});
 
 
 
vgridPanelBase.addButton({
    text: 'Make Map Numbers',
    handler: function(toggled){
        if (toggled) {
        makeMapNumbers(cities);
        vectorstore.loadData(cities.features, false)
} 
        else {
            length.deactivate();
        }
    }
});


       vgridPanelBase.addButton({
    	  text: "Delete Feature",
    handler: function() {
        vgridPanelBase.getSelectionModel().each(function(rec) {
            var feature = rec.get("feature");
            cities.removeFeatures([feature]);
                        popup.hide();
            
        })
        }
        });
        
        
        vgridPanelBase.addButton({
    	  text: "Delete All Features",
    handler: function() {
    cities.removeAllFeatures();
    //incase there is a popup present
                            popup.hide();
    }
        });  
       
       vgridPanelBase.addButton(new Ext.ux.Exporter.Button({
          component: vgridPanelBase,
          text: "Export grid as .xls"
        }));
        
    
 //this listens for a double click and then move the map to that place
 //so far the 5 is the best zoom
            vgridPanelBase.on('rowdblclick', function(){
                        popup.hide();
            var totalBounds;
            vgridPanelBase.getSelectionModel().each(function(rec){
            var feature = rec.get("feature");
            map.setCenter(new OpenLayers.LonLat(feature.geometry.x,feature.geometry.y),5);
          //  popup.hide();
            winGridBase.hide();
           createPopup(feature, cities);
           //make sure the popups can be accessed after this window is hidden
                       popupToggle = 1;
          //                              var ctrlSelectFeatures = new OpenLayers.Control.SelectFeature();
            //                   ctrlSelectFeatures.select(feature);

});
     });


            

vgridPanelBase.on('rowclick',  function(g, rowIdx,r)
            {
            popup.hide();

            rec = vectorstore.getAt(rowIdx);
                        var feature = rec.get("feature");
            map.setCenter(new OpenLayers.LonLat(feature.geometry.x,feature.geometry.y),5);
                        popup.hide();

     });





winGridBase= new Ext.Window({
	id: 'winGridBase',
	width: 900,
	height: 300,
	resizable: true,
	maximizable: true,
	minWidth: 350,
	minHeight: 200,
	title: 'Map Features',
	layout: 'border',
	closable: false,
	items: vgridPanelBase,
	closeAction:'hide' //otherwise closing the top will nerf the window
	});

  winGridBase.addButton({
      	  text: "Close",
       handler:     function() {
       popupToggle = 1;
       winGridBase.hide();}

  
  }); 
  
    
  winGridBase.show();       
              
}             

map.addLayers([coast, elevation, hillshade, waterc, inlandw, openw, roadsw, aquew, pAllLayers, cities, urbw]);               



//toolbar menu for language options and other things   
 //the buttons use the nameChanger function earlier to change the language of our labels   
    //maybe this could be collapsed into a function at some point
//toggle here since we don't ever want 2 languages active

//to select english

  action = new Ext.Button({
    text: 'English Labels',
        enableToggle: true,
            pressed: true,
    toggleGroup: toggleGroup,
    handler: function(toggled){
        if (toggled) {
        nameChanger(cities, contextEn);
        //redraw here
        } 
    }
});
    actions["english"] = action;
    toolbarItems2.push(action);


//to select greek
  action = new Ext.Button({
    text: 'Greek Labels',
        enableToggle: true,
    toggleGroup: toggleGroup,
    handler: function(toggled){
        if (toggled) {
        nameChanger(cities, contextGr);
        }
    }
});
    actions["greek"] = action;
    toolbarItems2.push(action);


//to select latin
  action = new Ext.Button({
    text: 'Latin Labels',
        enableToggle: true,
    toggleGroup: toggleGroup,
    handler: function(toggled){
        if (toggled) {
        nameChanger(cities, contextLa);
        } 
    }
});
    actions["latin"] = action;
    toolbarItems2.push(action);


//this enables the map numbers label


 action = new Ext.Button({
    text: 'Map Numbers',
    enableToggle: true,
    toggleGroup: toggleGroup,
    handler: function(toggled){
        if (toggled) {
                makeMapNumbers(cities);
                        vectorstore.loadData(cities.features, false)
               nameChanger(cities, contextMa);
        }
    }
});
    actions["mapnum"] = action;
    toolbarItems2.push(action);


//to select none
//since this is the defaut, this will start as pressed
  action = new Ext.Button({
    text: 'No Labels',
    enableToggle: true,
    toggleGroup: toggleGroup,
    handler: function(toggled){
        if (toggled) {
               nameChanger(cities, contextNo);
        }
    }
});
    actions["none"] = action;
    toolbarItems2.push(action);


toolbarItems.push({
        text: "Labels",
        menu: new Ext.menu.Menu({
            items: [
            toolbarItems2
            ]
        })
    });


//for now the array value of the changing time is hardcoded in. We will have to change this if we expand the time offerings
//again more kludging here. The version of extjs that plays nicely with geoext does not have a check box state function.
//therefore we are using the display property of our custom object to do double duty.
//move this to the top later
//we can make a function capture the behavior here

var arcButton = new Ext.menu.CheckItem(new Ext.Button({
    text: 'Archaic',
    handler: function(toggled){
    if (toggled) {
    if (timeContainer[0].dispT == false)
    {
    timeContainer[0].dispT = true;
    }
    else timeContainer[0].dispT = false;
    timeChanger(cities, timeContainer);
    }
    }
}));
timeArry.push(arcButton);

var classicButton = new Ext.menu.CheckItem(new Ext.Button({
    text: 'Classical',
    id: 'Classical',
     handler: function(toggled){
    if (toggled) {
    if (timeContainer[1].dispT == false)
    {
    timeContainer[1].dispT = true;
    }
    else timeContainer[1].dispT = false;
    timeChanger(cities, timeContainer);
    }
    }
}));

timeArry.push(classicButton);



var helButton = new Ext.menu.CheckItem(new Ext.Button({
    text: 'Hellenistic',
       handler: function(toggled){
    if (toggled) {
    if (timeContainer[2].dispT == false)
    {
    timeContainer[2].dispT = true;
    }
    else timeContainer[2].dispT = false;
    timeChanger(cities, timeContainer);
    }
    }
}));


timeArry.push(helButton);

var romButton = new Ext.menu.CheckItem(new Ext.Button({
    text: 'Roman',
      handler: function(toggled){
    if (toggled) {
    if (timeContainer[3].dispT == false)
    {
    timeContainer[3].dispT = true;
    }
    else timeContainer[3].dispT = false;
    timeChanger(cities, timeContainer);
    }
    }
}));

timeArry.push(romButton);

var latButton = new Ext.menu.CheckItem(new Ext.Button({
    text: 'Late',
     handler: function(toggled){
    if (toggled) {
    if (timeContainer[4].dispT == false)
    {
    timeContainer[4].dispT = true;
    }
    else timeContainer[4].dispT = false;
    timeChanger(cities, timeContainer);
    }
    }
}));


timeArry.push(latButton);

toolbarItems.push("-");

toolbarItems.push({
        text: "Time Period(s)",
        menu: new Ext.menu.Menu({
            items: [
            timeArry
            ]
        })
    });
  
  //probably will move this to the initalization above
      for (var i = 0; i < timeArry.length; i++)
        {
        timeArry[i].setChecked(true);
        }

        toolbarItems.push("-");
        
        
         action = new Ext.Button({
        text: 'Permalink',
        handler: function(){
            
            var l = permalinkProvider.getLink();
            var permalinkWindow = new Ext.Window({
                title: 'Permalink to the current map state',
                modal: true,
                layout: 'fit',
                width: 750,
                height: 85,
                closeAction:'hide',
                plain: true,
                resizable: false,
				html:'<input type="text" size="250" style="font-family:tahoma,\'lucida grande\',arial,sans-serif;background-color: #FFFFFF;font-size:11px;border: none" onMouseOver="select();" value="' + l + '" />',
                buttonAlign: 'center',
                buttons: [{
                    text: 'Close',
                    handler: function(){
                        permalinkWindow.hide();
                    }
                }]
            });
            permalinkWindow.show();
        }
    });
  
                     actions["permlink"] = action;

        
        

  
        
	    
	    var defaultAction = new GeoExt.Action({
	        text: "Select Feature / Navigate",
	        control: new OpenLayers.Control.SelectFeature(cities),
	        map: map,
	        activateOnEnable: true, //for switching
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        pressed: true,
	        tooltip: "navigate",
	        // check item options
	        group: "tools",
	        checked: true
	    });
//	    actions["select"] = action;

var measResult;

action = new GeoExt.Action({
	        text: "Measure",
	        toggleGroup: "tools",
	        control: new OpenLayers.Control.Measure(OpenLayers.Handler.Path, {
	        	          handlerOptions: {style: sty},
	        eventListeners: {
	        measure: function(evt) {
	        measResult = evt.measure;
	      /*
	        try{
	        winMeasResult.hide();
	        }
	        catch(e){}
	        fnMesShow(measResult);
     */
     alert("The measurement is " + evt.measure + " " + evt.units);
	        }
	        }
	        }),
	        map: map,
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        tooltip: "measurement",
	        // check item options
	        group: "tools"
	    });
	    actions["mes"] = action;
	    
	    
	    action = new GeoExt.Action({
	        text: "Drag Feature",
	        toggleGroup: "tools",
	        control: new OpenLayers.Control.DragFeature(cities),
	        map: map,
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        tooltip: "drag feature",
	        // check item options
	        group: "tools"
	    });
	    actions["drag"] = action;



 action = new GeoExt.Action({
	        text: "Draw Feature",
	        toggleGroup: "tools",
	        control: new OpenLayers.Control.DrawFeature(cities, OpenLayers.Handler.Point, {displayClass: 'pointButton', title: 'Add point',
	        featureAdded:function(feature) {
	        fakePID--;
	        //this might seem silly, but removing the feature allows for editing etc before adding
	        //otherwise the feature store does not seem to get the values
	        
	        formPanelAdd.getForm().setValues({addlong:feature.geometry.x, addlat:feature.geometry.y });
	        cities.removeFeatures([feature]);
	        
	        
	        win.show();

            },
	        handlerOptions: {style: sty}}),
	        map: map,
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        tooltip: "draw feature",
	        // check item options
	        group: "tools"
	    });
	    actions["draw"] = action;

            
 action = new GeoExt.Action({
        text: "Draw Line Work",
	        toggleGroup: "tools",
         control:new OpenLayers.Control.DrawFeature(cities,
                        OpenLayers.Handler.Path,
                        {displayClass: 'pointButton', title: 'Add point',
	        featureAdded:function(feature) {
	        fakePID--;
	        cities.removeFeatures([feature]);
	        win.show();
	        
	        },
	        	        handlerOptions: {style: sty}
})
	        ,
	        map: map,
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        tooltip: "draw feature",
	        // check item options
	        group: "tools"
});
	    actions["drawLine"] = action;

action = new GeoExt.Action({
        text: "Draw Polygon Areas",
	        toggleGroup: "tools",
	        
         control:new OpenLayers.Control.DrawFeature(cities,
                        OpenLayers.Handler.Polygon,
                        {displayClass: 'pointButton', title: 'Add Polygon',
	        featureAdded:function(feature) {
	        fakePID--;
	        	        cities.removeFeatures([feature]);
	        	        currentFeatureType = 'polygon';
	        win.show();
	        
	        },
	        	        handlerOptions: {style: sty}
})
	        ,
	        map: map,
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        tooltip: "draw feature",
	        // check item options
	        group: "tools"
});
	    actions["drawpoly"] = action;
	    
	    
action = new GeoExt.Action({
	text: "Polygon Select",
	toggleGroup: "tools",
	control:new OpenLayers.Control.DrawFeature(
			cities,
			OpenLayers.Handler.Polygon,
			{
				displayClass: 'pointButton', 
				title: 'Polygon Select',
				featureAdded:function(feature) 
					{
						cities.removeFeatures([feature]);
	        	        var areaGeomText = feature.geometry.toString();
	        	//this sets the geometry for the polygon search and refining search
	        	//we zero out everything else so a new polygon starts from scratch
	        	        formPanel.getForm().setValues({
	        	        formName:"",
	        	        formType:"",
	        	        formTime:"",
	        	        formPID:"",
	        	        formGeom:areaGeomText
	        	        });
	        	        Ext.Ajax.request(
	        	        	{
	        	        		url : 'polygon_return.php',
	        	        		method: 'GET',
	        	        		params : 
	        	        			{
	        	        				geom : areaGeomText
	        	        			},
	        	        		//if everything works add the features to a store that the user can choose from
	        	        		success: function(objServerResponse)
	        	        			{
	        	        				store.removeAll();
	        	        				try{
	        	        				store.loadData(geojson_format.read(objServerResponse.responseText));
	        	        				           var rows = String(store.getCount());
	        	        				           if (rows != '1'){
    var newTextItem = rows.concat(" Results");}
    else{
    var newTextItem = rows.concat(" Result");}
    }
    catch(e)
    {
    var newTextItem ='0 Results';
    }
    
    myTextItem.setText(newTextItem);
	        	        				defaultAction.enable(); //we want to switch to select mode after drawing the polygon so a user can select the new features
	        	        				winSearchLaunch.show(); //show the results window
	        	        			},
	        	        		failure : function(objServerResponse){
	        	        			}
	        	        		});
	        	        	},
	        	        handlerOptions: {style: sty}
	        	}),
	        map: map,
	        // button options
	        toggleGroup: "tools",
	        allowDepress: false,
	        tooltip: "Select features in an area",
	        // check item options
	        group: "tools"
});
	    actions["polySelect"] = action;
	    



    toolbarItems.push({
	        text: "Tools",
	        menu: new Ext.menu.Menu({
	            items: [
	                new Ext.menu.CheckItem(defaultAction),
	                new Ext.menu.CheckItem(actions["polySelect"]),
	                new Ext.menu.CheckItem(actions["mes"]),
	                new Ext.menu.CheckItem(actions["drag"]),
	                new Ext.menu.CheckItem(actions["draw"]),
	             	new Ext.menu.CheckItem(actions["drawLine"]),
	                new Ext.menu.CheckItem(actions["drawpoly"])
	             // for now this seems a bit useless   new Ext.menu.Item(actions["permlink"])
	                ]
	                })
	        		});
	        		
	        		        toolbarItems.push("-");

	        		
action = new Ext.Button({
  text: "Print",
            handler: function() {
                // convenient way to fit the print page to the visible map area
                printPage.fit(mapPanel, true);
                // print the page, optionally including the legend
                printProvider.print(mapPanel, printPage, legendPanel);
            }
            });
            
 action = new Ext.Button({
 
 text: "Export Map as .json",
 handler: function(){
 
 var geoJSON = new OpenLayers.Format.GeoJSON();
    geoJSONText = geoJSON.write(cities.features);

var geoJSONTextEscape = encodeURI(geoJSONText);

var body = Ext.getBody();
var frame = body.createChild({
    tag: 'iframe',
    cls: 'x-hidden',
    id: 'hidden_iframe',
    name: 'hidden_iframe'
});

var form = body.createChild({
    tag: 'form',
    cls: 'x-hidden',
    id: 'hidden_form',
    method: 'post',
    action: 'downloaddata.php',
    target: '_blank'
});

var input = form.createChild({
    tag: 'input',
    cls: 'x-hidden',
    id: 'hidden_input',
    name: 'jsondata',
    type: 'hidden',
    value: geoJSONTextEscape
});

form.dom.submit();


 }
 
 
 });
 
     actions["exportjson"] = action;


action = new Ext.Button({
 
 //may have to copy the layer to a new layer then change the attributes around for this kind of thing
 
 
 text: "Export Map as .kml",
 handler: function(){
var kmlex = new OpenLayers.Format.KML();
var kmlText = kmlex.write(cities.features);
var geoJSONTextEscape = encodeURI(kmlText);

var body = Ext.getBody();
var frame = body.createChild({
    tag: 'iframe',
    cls: 'x-hidden',
    id: 'hidden_iframe',
    name: 'hidden_iframe'
});

var form = body.createChild({
    tag: 'form',
    cls: 'x-hidden',
    id: 'hidden_form',
    method: 'post',
    action: 'downloaddatakml.php',
    target: '_blank'
});

var input = form.createChild({
    tag: 'input',
    cls: 'x-hidden',
    id: 'hidden_input',
    name: 'jsondata',
    type: 'hidden',
    value: geoJSONTextEscape
});

form.dom.submit();
 }
 
 
 });
   actions["exportkml"] = action;

            toolbarItems.push({
	        text: "Export",
	        menu: new Ext.menu.Menu({
	            items: [
	                new Ext.menu.Item(actions["exportjson"]),
	                new Ext.menu.Item(actions["exportkml"])
	                ]
	                })
	        		});
	        		
	        		        toolbarItems.push("-");

 var gridButton = new Ext.Button({
  text: "Map Features List",
            handler: function() {
            //vectorstore.reload();
fnCreateGrid();
            }
            });
            
            toolbarItems.push(gridButton);
            
            	        		        toolbarItems.push("-");
 var mapZoomButton = new Ext.Button({
  text: "Zoom To Large Map",
            handler: function() {
map.setCenter(new OpenLayers.LonLat(zoomFeature.geometry.x,zoomFeature.geometry.y),1); //zoom map to new feature
            }
            });

                    toolbarItems.push(mapZoomButton);

//end of map toolbar items

//get the geoext mappanel together
            
var mapPanel = new GeoExt.MapPanel(
{
	id: 'mapPanel',
	region: 'center',
	height: 300,
	width: 600,
	extent: extent,
	map: map,
	collapsible: true,
	title: 'Map',
	tbar: toolbarItems,
	    stateId: "map",
    prettyStateKeys: true // for pretty permalinks

});

var legendStore = new GeoExt.data.LayerStore({
    layers: [coast, elevation, hillshade, waterc, inlandw, openw, roadsw, aquew]
});


//this did not seem to work in the big layers object above
   var n_arrow = new OpenLayers.Layer.Vector( "Compass rose and copyright", 
                      {
                      displayInLayerSwitcher: false,
                      'attribution': "<img src='../images/awmc_rose2.png' width='84' height='100' align='right'/><br><br>"
                    });
                    
                    map.addLayer(n_arrow);


     var coast_o = new OpenLayers.Layer.WMS( "Locator Map", 
                    "/cgi-bin/mapserv?map=/sapientia/www/html/projects/awmc/mapfiles/over.map",
                    {layers: "over_m"},
                    {maxExtent: new OpenLayers.Bounds( -31.265750, -2.461050, 112.898610, 81.857360)
                    });
            
           
                var o_options = {layers: [coast_o]};
            map.addControl(new OpenLayers.Control.OverviewMap({mapOptions: o_options}));
            var scaleline = new OpenLayers.Control.ScaleLine();
            map.addControl(scaleline);
                        map.addControl(new OpenLayers.Control.MousePosition({formatOutput: formatLonlats}));
  
  
  var features = new Array();
var places = new OpenLayers.Layer.Vector("places");


var reader = new GeoExt.data.FeatureReader({},fieldDefs);

var store = new GeoExt.data.FeatureStore({
    reader: reader,
    fields: fieldDefs,
    autoLoad: false
});


function fn_submitForm(button,event){
//clear the store and the visual display
   var f = Ext.getCmp('formPanel');
   var nameparam = formPanel.getForm().findField("formName").getValue();
      var typeparam = formPanel.getForm().findField("formType").getValue();
      var timeparam = formPanel.getForm().findField("formTime").getValue();
      var pidparam = formPanel.getForm().findField("formPID").getValue();
      var geomparam = formPanel.getForm().findField("formGeom").getValue();

   if( f.getForm().isValid() == true)
   {
       Ext.Ajax.request({

    url : 'geojson2.php',
    method: 'GET',
    params : {
        name : nameparam,
        type : typeparam,
        timeperiod: timeparam,
        pid: pidparam,
        geomParam : geomparam 
    },
    //if everything works add the features to a store that the user can choose from
    success: function(objServerResponse){
    	        	        				store.removeAll();
    	        	        				//we need to catch an error if the geojson returns but has no features
	        	        				try{
	        	        				store.loadData(geojson_format.read(objServerResponse.responseText));
	        	        				           var rows = String(store.getCount());
	        	        				           if (rows != '1'){
    var newTextItem = rows.concat(" Results");}
    else{
    var newTextItem = rows.concat(" Result");}
    }
    catch(e)
    {
    var newTextItem ='0 Results';
    }    
    myTextItem.setText(newTextItem);
    },
    failure : function(objServerResponse){ 
    }
});}}


//at the moment the listeners for the change are pretty ugly but they work.
//otherwise the form panel submission did not seem to work (even with submit)
//this is something we are going to have to look into

var formPanel = new Ext.FormPanel({
title:'Search Criteria',
    height: 200,
    width: 400,
            autoScroll: true,
                    buttonAlign :'left',
                    url:'geojson.php',
    region: "west",
    id: "formPanel",
    items: [{
        xtype: "textfield",
        name: "formName",
        fieldLabel: "Name (any language)",
        id: "formName"
    },
    
     {
        xtype: "combo",
        name: "formType",
        fieldLabel: "Type (leave blank for any)",
            forceSelection: true, 
        id: "formType",
        store: new Ext.data.SimpleStore({
        fields: ['rules'],
        data : rulesDef 
    }),
    displayField: 'rules',
		        typeAhead: true,
		        mode: 'local',
		        width:147,
		        listWidth: 147
    },
    
    
    
       {
        xtype: "textfield",
        name: "formTime",
        fieldLabel: "Time Period",
        id: "formTime"
    },
        {
        xtype: "textfield",
        name: "formPID",
        fieldLabel: "Pleiades ID",
        id: "formPID"
    },
    {
        xtype: "textfield",
        name: "formGeom",
        fieldLabel: "Geometry",
        id: "formGeom"
    }
    ],
    keys: [
    { key: [Ext.EventObject.ENTER], handler: fn_submitForm }]
});

formPanel.addButton({
    text: "Clear Search Values",
    handler: function(){
    formPanel.getForm().setValues({
	        	        formName:"",
	        	        formType:"",
	        	        formTime:"",
	        	        formPID:"",
	        	        formGeom:""
	        	        });  
    },
    scope: formPanel
});



formPanel.addButton({
    text: "Search",
    handler: fn_submitForm,
    scope: formPanel
});




var myTextItem = new Ext.Toolbar.TextItem({text: '0 Results'});

gridPanel = new Ext.grid.GridPanel({
    title: "Results. Double click to add a feature to the map or use the buttons below",
    height: 500,
        width: 800,
            buttonAlign :'left',
    forceFit: true,
    region:"center",
    	bbar:[ {xtype: 'tbfill'},
    	myTextItem],  
    store: store,
     columns: [{
            header: "English Name",
            width: 100,
            dataIndex: "en_name",
            sortable: true
            }, 
           {
            header: "Search Result",
            width: 100,
            dataIndex: "searchrename",
            sortable: true
            }, 
             {
            header: "Type",
            width: 100,
            dataIndex: "featuretyp",
            sortable: true
            },
            {
            header: "Time Period",
            width: 100,
            dataIndex: "timeperiod",
            sortable: true
            },
             {
            header: "Greek Name",
            width: 100,
            dataIndex: "gr_name",
            sortable: true
            }, 
            {
            header: "Latin Name",
            width: 100,
            dataIndex: "la_name",
            sortable: true
            }, 
            {
            header: "Pleiades ID",
            width: 100,
            dataIndex: "pid",
            sortable: true
            }
            ],
    sm: new GeoExt.grid.FeatureSelectionModel()
        
});

gridPanel.addButton({
    	  text: "Add A Feature To Map",
       handler: function() {
        gridPanel.getSelectionModel().each(function(rec) {
            var feature = rec.get("feature");
            

            cities.addFeatures([feature]);

        })
        },
    scope: gridPanel
});

gridPanel.addButton({
    	  text: "Add All Results To Map",
       handler: function() {
       var rows = gridPanel.getStore().getCount( );
       for (var i = 0; i < rows; i++)
       {
       var featadd = store.getAt(i);
       var feature = featadd.get("feature");
       

            cities.addFeatures([feature]);  
       
             
        }},
    scope: gridPanel
});


        
            gridPanel.on('rowdblclick', function(g, rowIdx,r)
            {
            rec = store.getAt(rowIdx);
                        var feature = rec.get("feature");
                        

            cities.addFeatures([feature]);
                        
                        map.setCenter(new OpenLayers.LonLat(feature.geometry.x,feature.geometry.y), map.zoom); //center map to new feature
        });
        
     var selectCtrl = new OpenLayers.Control.SelectFeature(cities);
       
        
        
            // define "createPopup" function
    function createPopup(feature, cities) {
  

    //first ensure the current feature is what we want
currentFeature = feature;
//first the tab container
    

   
   //first the further information panel
   
   var explorePanel = new Ext.Panel({
    id: 'explorePanel',
    title: "Explore Further",
    bodyStyle: 'background-color:#FFFFFF;',
       autoScroll:true   
        }); 

   //now for the edit and info holder
   var popInfoPanel = new Ext.Panel({
    id: 'popInfoPanel',
    title: "Edit Information",
       autoScroll:true   
    }); 



 



   var toolsPanel = new Ext.Panel({
    id: 'toolsPanel',
    title: "Tools",
    bodyStyle: 'background-color:#FFFFFF;',
       autoScroll:true   
        }); 

   var delPanel = new Ext.Panel({
    id: 'delPanel',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;'
    }); 

    delPanel.addButton({
        	  text: "Delete Feature",
    handler: function() {
            cities.removeFeatures([feature]);
            popup.hide();
            }
  
});


     toolsPanel.add(delPanel);   


 
 
 
   


//NamesPanel.add(gridName);

//set the edit form with the feature information

       formPanelEdit.getForm().setValues({
    editformenName:feature.attributes.en_name,
editformgrName: feature.attributes.gr_name,
editformlaName: feature.attributes.la_name,
editformType: feature.attributes.featuretyp,
editformTime: feature.attributes.timeperiod,
editformPid: feature.attributes.pid,
editformPers: feature.attributes.perseus_li,
editformWiki: feature.attributes.wiki_link,
editformPath: feature.attributes.path,
editformSearchName: feature.attributes.searchrename,
    editlong:feature.geometry.x, 
    editlat:feature.geometry.y });


   
   popInfoPanel.add(formPanelEdit);
       //setup the URL for pelagios, pleiades, and the button. Every basic feature will have this but added ones (with a negative pid) will not.
   
   if(feature.attributes.pid > 0){
   
   
     //this is a hack
    //yup
    
    //get the names here


var nameDefs =[
			{name: 'name', type: 'string'},
            {name: 'language', type: 'string'}
            ];

var nameReader = new GeoExt.data.FeatureReader({},nameDefs);




var nameStore = new GeoExt.data.FeatureStore({
    reader: nameReader,
    fields: nameDefs,
    autoLoad: false
});
    
        names_l.removeAllFeatures();

     Ext.Ajax.request({

    url : 'name_return.php',
    method: 'GET',
    params : {
    pid: feature.attributes.pid
    },
    //if everything works add the features to a store that the user can choose from
    success: function(objServerResponse){
    nameStore.loadData(geojson_format.read(objServerResponse.responseText));
         var rows = nameStore.getCount();
       for (var i = 0; i < rows; i++)
       {
       var featadd = nameStore.getAt(i);
       var feature = featadd.get("feature");
            names_l.addFeatures([feature]);
             
        } 
    },
    failure : function(objServerResponse){ 
    }
});
   
   
   

//put into grid

gridName = new Ext.grid.GridPanel({
    //title: "Results",
    //height: 500,
    //        buttonAlign :'left',
    //forceFit: true,
  //  region:"center",
         autoHeight: true,
    store: nameStore,
     columns: [{
            header: "Name",
           // width: 100,
            dataIndex: "name",
            sortable: true
            }, 
           {
            header: "Language",
           // width: 100,
            dataIndex: "language",
            sortable: true
            }
            ]        
});

   





//display grid


 var NamesPanel = new Ext.Panel({
    id: 'NamesPanel',
    title: "Names",
    bodyStyle: 'background-color:#FFFFFF;',
       autoScroll:true,
       items: gridName   
        });
        
                    NamesPanel.addButton({
    text: 'Export',
    handler: function() {
    
 var geoJSON = new OpenLayers.Format.GeoJSON();
 var geoJSONText = geoJSON.write(names_l.features);
var geoJSONTextEscape = encodeURI(geoJSONText);


var body = Ext.getBody();
var frame = body.createChild({
    tag: 'iframe',
    cls: 'x-hidden',
    id: 'hidden_iframe',
    name: 'hidden_iframe'
});

var form = body.createChild({
    tag: 'form',
    cls: 'x-hidden',
    id: 'hidden_form',
    method: 'post',
    action: 'grid_out.php',
    target: '_blank'
});

var input = form.createChild({
    tag: 'input',
    cls: 'x-hidden',
    id: 'hidden_input',
    name: 'jsondata',
    type: 'hidden',
    value: geoJSONText
});

form.dom.submit();
/*

 Ext.Ajax.request({

    url : 'grid_out.php',
    method: 'POST',
    params : {
    jsondata: 
    },
    //if everything works add the features to a store that the user can choose from
    success: function(objServerResponse){
    alert(objServerResponse);
    },
    failure : function(objServerResponse){ 
    }
});*/

    }
});


   
   
    var pelagUrl = pelagApi.concat(feature.attributes.pid);
    var pleaUrl = pleaBase.concat(feature.attributes.pid);


   var plPanel = new Ext.Panel({
    id: 'plPanel',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;',
    items:[{    
    html: " Clicking the button below will take you to the Pleiades page for " + feature.attributes.en_name +"."
} 
    ]
    }); 
    
    
    plPanel.addButton({
    text: 'Pleiades',
    handler: function() {
    window.open(pleaUrl);
           }
});

    

 
   var spacePanel_p = new Ext.Panel({
    id: 'spacePanel_p',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;',
    items:[{
    html: "<br>"
    } 
    ]
    }); 
 
explorePanel.add(plPanel);
explorePanel.add(spacePanel_p);






   var pelagPanel = new Ext.Panel({
    id: 'pelagPanel',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;',
    items:[{    
    html: " Clicking the button below will take you to a page that lists all the instances of " + feature.attributes.en_name + " in the Pelagios linked data network."
} 
    ]
    }); 
    
    
    pelagPanel.addButton({
    text: 'Pelagios',
    handler: function() {
    window.open(pelagUrl);
           }
});

    

 
   var spacePanel = new Ext.Panel({
    id: 'spacePanel',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;',
    items:[{
    html: "<br>"
    } 
    ]
    }); 
 
explorePanel.add(pelagPanel);
explorePanel.add(spacePanel);

}
   
//see if there is a wiki link for the feature in question. If so, create a button and add it to the external buttons tab

   if (feature.attributes.wiki_link)
   {
     var wikiPanel = new Ext.Panel({
     id: 'wikiPanel',
     border: false, 
     frame : false,
     bodyCssClass: 'x-panel-mc',
     bodyStyle: 'background-color:#FFFFFF;',
     items:[{
     html:" This button will take you to Wikipedia entry for " + feature.attributes.en_name +"."
     }]
     }); 
    
    
    wikiPanel.addButton({
    text: 'Wikipedia',
    handler: function() {
    window.open(feature.attributes.wiki_link);
    }
});

    
 
   var spacePanel2 = new Ext.Panel({
   id: 'spacePanel2',
   border: false, 
   frame : false,
   bodyCssClass: 'x-panel-mc',
   bodyStyle: 'background-color:#FFFFFF;',
   items:[{
   html: "<br>"
   }]
   }); 

explorePanel.add(wikiPanel);
explorePanel.add(spacePanel2);

    }
    
//flickr. For now we are not checking if the resource exists

    var flickrFinal = flickrUrl.concat(feature.attributes.pid);



   var flickrPanel = new Ext.Panel({
    id: 'flickrPanel',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;',
    items:[{    
    html: "This button takes you to any Flickr pages tagged with the Pleiades ID for " + feature.attributes.en_name + "."
} 
    ]
    }); 
    
    
    flickrPanel.addButton({
    text: 'Flickr',
    handler: function() {
    window.open(flickrFinal);
           }
});


   var spacePanel3 = new Ext.Panel({
   id: 'spacePanel3',
   border: false, 
   frame : false,
   bodyCssClass: 'x-panel-mc',
   bodyStyle: 'background-color:#FFFFFF;',
   items:[{
   html: "<br>"
   }]
   }); 

explorePanel.add(flickrPanel);
explorePanel.add(spacePanel3);



//we want the default tab to show feature information


        
        
        

    var citytab = new Ext.TabPanel({
   region:'center',
   deferredRender:false,
   buttonAlign: 'left',
   activeTab:0,
   autoScroll:true,
   height: 300,
   items:[popInfoPanel,NamesPanel,explorePanel,toolsPanel]  
   });
/*
citytab.add(popInfoPanel);
citytab.add(NamesPanel);
citytab.add(explorePanel);
citytab.add(toolsPanel);    
  */  
       popup = new GeoExt.Popup({
            title: feature.attributes.en_name,
            id: popcounter++,
            location: feature,
            width:340,
            height:380,
            map: mapPanel,
            maximizable: true,
            collapsible: true,
            closeAction:'hide', //otherwise closing the top will nerf the window
           // html: pelagUrl
            items: citytab,
                keys: [
    { key: [Ext.EventObject.ENTER], handler: fn_submitForm }]
        });
       
   popup.addButton({
    text: "Ok",
    handler: fn_editsubmitForm
    });        
   
           
    popup.addButton({
    	  text: "Close",
       handler:     function() {
       selectCtrl.unselect(feature);
       popup.hide();}
});

        // unselect feature when the popup
        // is closed
        
         popup.on({
            close: function() {
                if(OpenLayers.Util.indexOf(cities.selectedFeatures,
                                           this.feature) > -1) {
                    selectCtrl.unselect(this.feature);
                }
            }
        });
        
        

        popup.show();
 
  }      
        
        
        

searchPanel = new Ext.Panel({
    layout: "border",
    region: "center",
    width: 270,
     width: 300,
        autoScroll: true,
    items: [formPanel,gridPanel]
});



  
winSearchLaunch= new Ext.Window({
	id: 'winSearchLaunch',
	width: 1150,
	height: 300,
	resizable: true,
	maximizable: true,
	minWidth: 350,
	minHeight: 200,
	title: 'Search',
	layout: 'border',
	items: searchPanel,
	closeAction:'hide' //otherwise closing the top will nerf the window
	});
	

  winSearchLaunch.addButton({
      	  text: "Close",
       handler:     function() {
       winSearchLaunch.hide();}

  
  });
  
  
//for the cultural data node
var layerList = new GeoExt.tree.LayerContainer({
    text: 'Cultural Data',
    layerStore: mapPanel.layers,
    leaf: false,
    expanded: true,
     loader: 
     {
        filter: function(record) 
        {
            return record.get("layer").options.group =="culture"
        }
    }
});

//for the physical data node
var layerList2 = new GeoExt.tree.LayerContainer({
    text: 'Physical Data',
    layerStore: mapPanel.layers,
    leaf: false,
    expanded: true,
     loader: {
        filter: function(record) 
        {
            return record.get("layer").options.group == "physical"
        }
    }
});



var layerRoot = new Ext.tree.TreeNode(
{
text: "Layers",
expanded: true
}
);

layerRoot.appendChild(layerList);
layerRoot.appendChild(layerList2);

//put everything in the tree

var layerTree = new Ext.tree.TreePanel({
    title: 'Map Layers',
    root: layerRoot,
    enableDD: true,
    width: 100,
    height: 200,
    autoScroll: true,
    collapsible: true,
    region: 'center'
    });
    
    
    

var searchPanelTop = new Ext.Panel({
layout: 'border',
region: 'north',
title: 'Search',
collapsible: true,
           buttonAlign: 'center'

});

searchPanelTop.addButton({
    	  text: "Search For Features",
       handler:     function() {
       winSearchLaunch.show();}
});



/*
searchPanelTop.addButton({
    	  text: "Bounds",
       handler:     function() {
       alert(map.center.lat);
       }
});


*/



var vecLegend = new  GeoExt.VectorLegend({
clickableSymbol: false,
clickableTitle: false,
enableDD: true,
rules: legendRules,
width: 100,
    height: 100,
    region: 'center',
        symbolType: "Point",
            autoScroll: true

});




//legend panel
//legend panel

var legendPanel = new GeoExt.LegendPanel({
    layerStore: legendStore,
        title: 'Overlay Legend',
    autoScroll: true,
    region: 'south',
    height: 100,
    width: 100,
    collapsible: true,
    defaults: {
    style: 'padding:5px',
    baseParams: {
    FORMAT: 'image/png',
    LEGEND_OPTIONS: 'forceLabels:on'
    }
    }
});


var legPanel = new Ext.Panel(
{
    title: 'Features Legend',
layout: 'border',
region: 'south',
width: 100,
    height: 250,
collapsible: true,
items:[vecLegend, legendPanel]
}
);



//formatting panels below. This can be changed around for a different UI look if we want

var rPanel = new Ext.Panel(
{
layout: 'border',
region: 'east',
width: 200,
collapsible: true,
items:[searchPanelTop, layerTree, legPanel]
}
);



//for the wms feature info

function createPopup2(popuptext, popupanchor)

{
//we are going to use the popup text to generate a window that is like the main window, only without the ability to change anything. A user will be able to create an "interactive" feature with a button

//first we are going to query the database for the pid. Since this is from a layer, the pid should be there. Perhaps an error checking mechanism should be put here to catch a weird instance if this is not the case

//CONTINUE TYPING
//CONTINUE TYPING

//next we will reuse our popup to display the information we would like. We may have to separate the store and add a boleen object to the function to indicate the correct buttons that should be loaded on each iteration of the basic window.
//at the same time there is a possibility for more than one feature. For testing we will use one: in the future this may be a grid or something like that to allow for more flexibility
//yikes

var textpid = String(popuptext);
textpid.replace(/\s+/g,'');
textpid.replace(/\n/g, '');

if (textpid.length > 2){

var wmsStore = new GeoExt.data.FeatureStore({
    reader: reader,
    fields: fieldDefs,
    autoLoad: false
});

var tempfeature;
     Ext.Ajax.request({

    url : 'gojson_wmsinfo.php',
    method: 'GET',
    params : {
    pid: textpid
    },
    //if everything works add the features to a store that the user can choose from
    success: function(objServerResponse){
    wmsStore.loadData(geojson_format.read(objServerResponse.responseText));
         var rows = wmsStore.getCount();
       for (var i = 0; i < rows; i++)
       {
       var featadd = wmsStore.getAt(i);
       var feature = featadd.get("feature");
       tempfeature = feature; 
            citiesTemp.addFeatures([feature]);
             
        }             
        
        createPopup(tempfeature, citiesTemp);

    },
    failure : function(objServerResponse){ 
    }
});
/*
featcounter++;
   var plPanel = new Ext.Panel({
    id: 'plPanel',
    border: false, 
    frame : false,
    bodyCssClass: 'x-panel-mc',
    bodyStyle: 'background-color:#FFFFFF;',
    html: popuptext
    });
    
    featPop = new GeoExt.Popup({
                    title: 'Feature information',
                    id: 'feat' + featcounter,
                    closeAction:'hide',
                    width: 400,
                    height: 100,
                    autoScroll: true,
                    map: map,
                    location: popupanchor,
                    items:[plPanel]
                    });
                    
                    if (popuptext)
                    {
                    featPop.show();
}
*/

}
}



var popupanchor;
var popuptext;

        info = new OpenLayers.Control.WMSGetFeatureInfo({
            url: baseUrl, 
            title: 'Identify features by clicking',
            maxFeatures: 1,
            queryVisible: true,
                            layers: [pAllLayers,urbw],
            eventListeners: {
                getfeatureinfo: function(event) {
                featPop.hide();
                popuptext = event.text;
               popupanchor = map.getLonLatFromPixel(event.xy)
                    createPopup2(popuptext, popupanchor);
                    
                }
            }
        });
        map.addControl(info);
        info.activate();





//put everything together here

var mainWin = new Ext.Window({
        title: "AWMC: À-la-carte Map",
        id:'mainWin',
             resizable: true,
        maximizable: true,
        constrain: true,
        height: 700,
        width: 1200,
        layout: "border",
   // items: [mapPanel, vgridPanelBase, rPanel]
   items: [mapPanel, rPanel],
        keys: [
    { key: [Ext.EventObject.ENTER], handler: function(){winSearchLaunch.show()} }]
    });
    
    mainWin.show();
    //lets fill the screen so that all the controls are immediately visible
    mainWin.maximize();
//align the search window here


var zoomFeature = new OpenLayers.Feature.Vector( new OpenLayers.Geometry.Point(33.9,47)); 


//map.zoomToExtent(extent);
map.setCenter(new OpenLayers.LonLat(zoomFeature.geometry.x,zoomFeature.geometry.y),1); //zoom map to new feature


       //we are having a problem with a hidden grid- this may solve it
try{
            cities.addFeatures([zoomFeature]);
                        cities.removeFeatures([zoomFeature]);
}
catch(e){}


 }); //end of large function
 
 //other "outside" functions go here

//end of all the js stuff
</script>


</head>
<body bgcolor="#333333">
If You closed the map by mistake, simply hit the refresh button on your browser.
</body>
</html>	
?>
