<?php
/*
 *  this file serves as the main visual-frontend
 *  of the system. final results are displayed using google earth plugin
 *  and basic tweaking functionality of various parameters
 *  is also included (accessible from the lefthand panel). 
 */
require_once('verify.php');
require_once('bin/load_config.php');	
require_once("bin/idgen.php");
require_once('bin/userData.php');
require_once('bin/color.php');
require_once('bin/colorManager.php');

 // initialize server-side globals
$queryID = isset($_REQUEST["QID"])? $_REQUEST["QID"] : '0b6f948d14f516e52dbe6f469a8dbbaf';

include_once("bin/kml_render_globals.php");

$idg = new idGen($queryID);
$filename='queries/'.$idg->getDirName().'/result.kmz';
$userFilename = 'queries/'.$idg->getDirName().'/'.$GLOBALS["username"].'-result.kmz';
if(file_exists($userFilename))
	$filename = $userFilename;

$base_url = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/';
$full_url = $base_url.$filename."?".rand(0,10000000000);
$full_user_url = $base_url.$userFilename."?".rand(0,10000000000);

$key = $API_KEY;

$cm = new colorManager($username,$queryID);								 
$COLOR_LIST = $cm->getColorList();
?>
<html>
<head>
    <title>DIMES PoP/AS Visual FrontEnd</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv='cache-control' content='no-cache'>
	<meta http-equiv='expires' content='0'>
	<meta http-equiv='pragma' content='no-cache'>

	<!-- ExtJS 2.2 -->
	<link rel="stylesheet" type="text/css" href="js/ext-2.2/resources/css/ext-all.css">
    <script type="text/javascript" src="js/ext-2.2/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="js/ext-2.2/ext-all.js"></script>
    
    <!-- jQuery -->
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <!-- <script type="text/javascript" src="js/loadData.js"></script> -->
    
    <!-- ExtJS GEarthPanel Plugin -->
    <link rel="stylesheet" type="text/css" href="css/Ext.ux.GEarthPanel-1.1.css" />
    <link rel="stylesheet" type="text/css" href="css/visual.css" />
    <script type="text/javascript" src="http://www.google.com/jsapi?key=<?php echo $key ?>"></script>
  	<script type="text/javascript" src="js/Ext.ux.GEarthPanel-1.1.js"></script>
  	
  	<!-- ExtJS ColorPicker Widget -->
  	<script language="javascript" src="js/ColorPicker/ux/widgets/ColorPicker.js"></script>
    <script language="javascript" src="js/ColorPicker/ux/widgets/form/ColorPickerField.js"></script>
    <link rel="stylesheet" type="text/css" href="js/ColorPicker/resources/color-picker.ux.css" />
    
    <!-- local paging for ExtJS Grids -->
    <script type="text/javascript" src="js/Ext.ux.data.PagingMemoryProxy.js"></script> 

	<!-- encoding Json on client-side -->
	<script type="text/javascript" src="js/JSON-js/json2.js"></script>
	
	<!-- extJS slider 
	<script type="text/javascript" src="js/ext-2.2/examples/slider/slider.js"></script>
    <script type="text/javascript" src="js/ext-2.2/examples/slider/SliderTip.js"></script>
    <link rel="stylesheet" type="text/css" href="js/ext-2.2/examples/slider/slider.css"> -->
    
    <script type="text/javascript">
    	
    	// client-side globals
    	var ge;
    	var QID = '<?php echo $queryID; ?>';

		// load google earth plugin libraries 
        google.load("earth", "1");
        google.load("maps", "2.xx");
        
        
        Ext.onReady(function(){

            // Create Google Earth panel
            var earthPanel = new Ext.ux.GEarthPanel({
                region: 'center',
                contentEl: 'eastPanel',
                margins: '5 5 5 0'
            });

            // Create control panel
            var controlPanel = new Ext.Panel({
                region: 'west',
                contentEl: 'westPanel',
                title: 'Control Panel',
                width: 395, 
                border: true,
                collapsible: true,
                // top , right , bottom , left
                margins: '5 0 5 0',
                layout: 'accordion',
                layoutConfig: {
                    animate: true,
                },
                defaultType: 'panel',
                defaults: {
                    bodyStyle: 'padding: 10px',
                }
            });
			
			// create 'download kml file' panel
            var downloadPanel = new Ext.Panel({
				contentEl: 'downloadPanel',
				border: false,
				defaultType: 'panel'	
			});
			
			// create Dimes header panel
			var northPanel = new Ext.Panel({
				region: 'north',
				contentEl: 'northPanel',
				autoHeight: true,
		        //autoWidth: true,
				border: false,
				bodyBorder: false,
				//defaultType: 'panel',
				collapsible: false,
				split: false
			});
		    
			// create asn-color preferences panel
			function getColorPanel(){
				
				// num of ASN's per grid-page
				var page_size = 10;

				// load user color-preferences from colorManager
				// extract ISP / Country from as_info.xml
				var myData = [
					<?php
						$as_list = $cm->getASList();
						$as_info_xml = simplexml_load_file('xml/ASN_info.xml');
						foreach($as_list as $asn){
							$as_info = $as_info_xml->xpath("/DATA/ROW[ASNumber=".$asn."]");
							if(!empty($as_info)){
								$isp_name = (string)$as_info[0]->ISPName;
								$country = (string)$as_info[0]->Country;
							} else {
								$isp_name = 'unknown';
								$country = 'unknown';
							}
							echo "[".$asn.",'".$isp_name."','".$country."','#".$COLOR_LIST['asn'][$asn]->web_format()."'],";
						}
					?>
			    ];
				
				// aider functions for rendering the grid with colorPicker widgets
				var createWidget = function(val, id, r) {
			        var cpf = new Ext.ux.ColorPickerField({
				        //fieldLabel: '',
				        value: val
				    });
				    cpf.on('valid', function(field) {
				        r.set('color',field.getValue());
				    });
				    cpf.render(document.body, id);
		    	}
		    	
		    	var renderColorPicker = function(value, metadata, record, rowIndex, colIndex, store){
			    	var id = Ext.id();
			    	createWidget.defer(1, this, [value, id, record]);
			    	return '<div id="' + id + '"></div>';
			    }
			   
			   // create the Data Store
				var store = new Ext.data.Store({
					proxy: new Ext.ux.data.PagingMemoryProxy(myData),
					reader: new Ext.data.ArrayReader({id:0}, [
						{name: 'asn', type: 'int'},
						{name: 'isp'},
						{name: 'country'},
				        {name: 'color' },
					]),
					remoteSort: true,
				});
				
				/*	
			    var store = new Ext.data.Store({
			    	//autoLoad: true,
					remoteSort: true,
					proxy: new Ext.data.HttpProxy({
						url: 'render_kml.php',
						method: 'POST'
					}),
					baseParams: {func: 'getASNColorList', queryID: QID},
					reader: new Ext.data.JsonReader({
						id: 'asn',
						root: 'asn-colors',
						totalProperty: 'totalCount',
			        	fields: [
				           {name: 'asn', type: 'int'},
				           {name: 'color' },
				        ],
					})
				});
				store.setDefaultSort('asn', 'ASC');
				*/
			    
			    //create paging bar	    
			    var pagingBar = new Ext.PagingToolbar({
			        pageSize: page_size,
			        //displayInfo: true,
			        //displayMsg: 'Displaying {0} - {1} of {2}',
			        //emptyMsg: "No data to display",
			        store: store
			    });
						    
			    // create the Grid
			    var asnPanel = new Ext.grid.GridPanel({
			    	title: 'ASN Color Selection',
			    	//frame: true,
			    	autoHeight:true,
			        //width: 280,
			    	border: false,
			    	selModel: new Ext.grid.RowSelectionModel(),
			        store: store,
			        columns: [
			            {id:'asn',header: "ASN", width: 37, align: 'center', sortable: true, dataIndex: 'asn'},
			            {id:'isp',header: "ISP", width: 85, align: 'center', sortable: true, dataIndex: 'isp'},
			            {id:'country',header: "Country", width: 95, align: 'center', sortable: true, dataIndex: 'country'},
			            {id:'color', header: "Color", width: 145, align: 'center', sortable: true, renderer: renderColorPicker, dataIndex: 'color'},
			        ],
			        stripeRows: true,
			        viewConfig: {
			         	autoFill:true,
			            forceFit:true
			        },
			        autoExpandColumn:'color',
			        tbar: pagingBar
			        //bbar: submitToolBar
			    });
			    
			    
    			store.on('load', function(obj,records){
    				/*
					//show only relevant ASNs
					store.filterBy(function(record,id){
	    				return ($.inArray(parseInt(id), as_list) != -1);
	    			});
	    			*/
					
					// show paging bar if numOfRecords bigger than pageSize
					var pagingTbar = asnPanel.getTopToolbar(); 
	    			if(store.getCount() >= page_size){
	    				//pagingTbar.enable();
	    				pagingTbar.show();
	    				store.load({params:{start:0, limit: page_size}});
	    			} else {
	    				//pagingTbar.disable();
	    				pagingTbar.hide();
	    			}
	    				
    			}, this, {single:true});
			    
			    // trigger the data store load
    			store.loadData(myData);
    			
    			
    			//////////////////////////////////////////////////
    			
    			// creating lower section of the panel
    			var intraColor = '<?php echo EDGES_INTRA_COLOR; ?>';
    			var interColor = '<?php echo EDGES_INTER_COLOR; ?>';
    			var bySrcAS = <?php echo ((EDGES_COLORING_SCHEME=='bySrcAS')? 'true':'false'); ?>;
    			
		    	var intra = new Ext.ux.ColorPickerField({
		    		name: 'EDGES_INTRA_COLOR',
			        fieldLabel: 'Intra-Connectivity',
			        value: intraColor,
			        labelStyle: 'width: 120px; margin-left: 20px;'
			    });
			    intra.on('valid', function(field) {
			        intraColor = field.getValue();
			    });
			    
		    	var inter = new Ext.ux.ColorPickerField({
		    		name: 'EDGES_INTER_COLOR',
			        fieldLabel: 'Inter-Connectivity',
			        value: interColor,
			        labelStyle: 'width: 120px; margin-left: 20px;'
			    });
			    inter.on('valid', function(field) {
			        interColor = field.getValue();
			    });
			    
			    var edgesColoring = new Ext.form.RadioGroup ({
			            hideLabel: true,
			            columns: 1,
			            items: [
			                {boxLabel: 'Same As SourceAS', name: 'EDGES_COLORING_SCHEME', inputValue: 'bySrcAS', checked: bySrcAS}, 
			                {boxLabel: 'By Connectivity Type:', name: 'EDGES_COLORING_SCHEME', inputValue: 'static', checked:!bySrcAS },
			            ]
			    });
			    
    			var items = [{
			        xtype:'fieldset',
			        autoHeight: true,
			        items: [edgesColoring,intra,inter]
			    }];
	
		    	// Create FormPanel with all layers
		        var edgesPanel = new Ext.FormPanel({
		            title: 'Edge Color Selection',
		            //frame: true,
			        border: false,
			        autoHeight: true,
			        autoWidth: true,
			        style: 'margin-top: 10px;',
			        formId: 'edgesForm',
			        saveToGlobals: false,
			        url: 'render_kml.php',
			        labelWidth: 120,
			        items: items
		        });
    			/////////////////////////////////////////////////
    			var form = edgesPanel.getForm();
    			
    			// create lower tool bar with 'save changes' / 'save as default' buttons			
				var submitToolBar = new Ext.Toolbar ({
					items:	[{
								pressed: false,
					            enableToggle:true,
					            text: 'Save As Default',
					            //cls: 'x-btn-text-icon details',
					            toggleHandler: function(btn, pressed){
					            	edgesPanel.saveToGlobals = ((edgesPanel.saveToGlobals)? false : true);
					            }

			        		},'-',{
					            text: 'Submit Changes',
					        	handler: function() {
					    			 var sm = store.getModifiedRecords();
					    			 var arr = [];
					    			 for (i=0; i<=sm.length-1; i++) {
					        			arr.push([sm[i].get('asn'),sm[i].get('color')]);
					    			 }
					    			 var tmp_str = JSON.stringify(arr);

					    			 Ext.Ajax.request({
					        		 	url: 'render_kml.php',
					        			method: 'POST',
					        			params: {func: 'submitColorPrefs', queryID: QID, color_string: tmp_str, global: edgesPanel.saveToGlobals},
					        			success: function(obj, request) {
					            			var resp = obj.responseText;
					            			var result = [];
					            			if (resp != 0) result = Ext.util.JSON.decode(resp);
					            			if (result.success){
					            				if (form.isValid()) {
					            					form.submit({
							                        	params: {queryID: QID, func: 'renderKML', panel: 'edges', submitted: 'yes', global: edgesPanel.saveToGlobals},
							                        	waitMsg: 'rendering kml...',
							                        	waitTitle: 'kml-render-engine',
							                        	timeout:30000,
							                            success: function(form, action) {
							                               reloadKML();
							                            },
							                            failure: function(form, action) {
							                                Ext.Msg.alert('Failed', action.result.msg);
							                            }
						                        	}); 
												} else {
													Ext.Msg.alert('Invalid Data', 'Please correct form errors.')
												}
					            			} else {
					                			alert('Failed:' + result.msg);
					            			}
					        			}
					    			});
					        	}
			        		}]
		        });
				
				// create a unified panel from both parts
    			var colorsPanel = new Ext.Panel({
		            title: 'Color Management',
			        border: false,
			        autoHeight: true,
			        autoWidth: true,
			        labelWidth: 120,
			        items: [asnPanel,edgesPanel],
			        bbar: submitToolBar
		        });
    			
			    return colorsPanel;
			}
			
			// create kml-render-options panel
			function  getGlobalsPanel(){
		
				var globalParams = {
					MIN_LINE_WIDTH: 	 { value: <?php echo MIN_LINE_WIDTH; ?>, 	type: 'number',		name: 'Min Edge Line Width',min:1,max:10,isFloat:false,delta:1},
					MAX_LINE_WIDTH: 	 { value: <?php echo MAX_LINE_WIDTH; ?>,	type: 'number',		name: 'Max Edge Line Width',min:1,max:10,isFloat:false,delta:1},
					TRANSPARENCY: 	 	 { value: <?php echo TRANSPARENCY; ?>,	type: 'number',		name: 'Line Transparency',min:0,max:255,isFloat:false,delta:1},
					INITIAL_ALTITUDE:	 { value: <?php echo INITIAL_ALTITUDE; ?>,	type: 'number',		name: 'Initial ASN Altitude',min:0,max:10000,isFloat:false,delta:10},
					ALTITUDE_DELTA:		 { value: <?php echo ALTITUDE_DELTA; ?>,	type: 'number',		name: 'ASN Altitude Delta',min:0,max:500000,isFloat:false,delta:10,expopnential:true},
					STDEV_THRESHOLD: 	 { value: <?php echo STDEV_THRESHOLD; ?>,	type: 'number',		name: 'Standard Deviation Threshold',min:0.1,max:3,isFloat:true,delta:0.1},
					DRAW_CIRCLES:		 { value: <?php echo DRAW_CIRCLES; ?>,	type: 'checkbox',	name: 'PoP Location Convergence Radiuses'},
					INTER_CON: 			 { value: <?php echo INTER_CON; ?>,	type: 'checkbox',	name: 'Inter-Connectivity'},
					INTRA_CON: 			 { value: <?php echo INTRA_CON; ?>,	type: 'checkbox',	name: 'Intra-Connectivity'},
					CONNECTED_POPS_ONLY: { value: <?php echo CONNECTED_POPS_ONLY; ?>,	type: 'checkbox',	name: 'Connected PoPs only'},
					ASN_EMBEDDED_IN_PLACEMARK: { value: <?php echo ASN_EMBEDDED_IN_PLACEMARK; ?>,	type: 'checkbox',	name: 'Embed ASN In Placemarks'},
					//BLACK_BACKGROUND: 	 { value: <?php echo BLACK_BACKGROUND; ?>,	type: 'checkbox',	name: 'Black Background'},
					USE_COLOR_PICKER: 	 { value: <?php echo USE_COLOR_PICKER; ?>,	type: 'hidden',	name: 'Web-Safe Color-Picking'}
			   };
			
			
			    var items = [];

			    for (param in globalParams){
			    	if(globalParams[param]['type']=='checkbox') {
						items.push({
							xtype: 'checkbox',
				            boxLabel: globalParams[param]['name'],
				            checked: globalParams[param]['value'],
				            hideLabel: true,
				            name: param
						});
					}
					if(globalParams[param]['type']=='number') {
						items.push({
				            xtype: 'numberfield',
			                fieldLabel: globalParams[param]['name'],
			                value: globalParams[param]['value'],
			               	minValue: globalParams[param]['min'],
			                maxValue: globalParams[param]['max'],
			                allowDecimals: globalParams[param]['isFloat'],
			                name: param
						});
					}
			    }
			    
		        // Create FormPanel with all layers
		        var globalsPanel = new Ext.FormPanel({
		            title: 'Kml Renderer Options',
			        border: false,
			        saveToGlobals: false,
			        autoHeight: true,
			        autoWidth: true,
			        formId: 'globalsForm',
			        labelWidth: 120,
			        url: 'render_kml.php',
			        items: items,
			        bbar: new Ext.Toolbar ({
						items:	[{
									pressed: false,
									enableToggle:true,
									text: 'Save As Default',
									//cls: 'x-btn-text-icon details',
									toggleHandler: function(btn, pressed){
										globalsPanel.saveToGlobals = ((globalsPanel.saveToGlobals)? false : true);
									}
					
								},'-',{
									text: 'Submit Changes',
									handler: function() {
										var form = globalsPanel.getForm(); // get the basic form
		                    			if (form.isValid()) { // make sure the form contains valid data before submitting
											 form.submit({
												params: {queryID: QID, submitted: 'yes', global: globalsPanel.saveToGlobals ,func: 'renderKML', panel: 'default' },
												waitMsg: 'rendering kml...',
												waitTitle: 'kml-render-engine',
												timeout:30000,
												success: function(form, action) {
												   reloadKML();
												},
												failure: function(form, action) {
													Ext.Msg.alert('Failed', action.result.msg);
												}
											});
										} else {
											Ext.Msg.alert('Invalid Data', 'Please correct form errors.')
										}
									}
								}]
					})
		        });
		        
		        return globalsPanel;
		    }
		    
		    // discard previous loaded KML data, and fetch the new one
		    function reloadKML(){
		    	earthPanel.resetKml();
		    	first_time = true;
		    	first_time2 = true;
		    	earthPanel.fetchKml('<?php echo("$full_user_url");?>?'+Math.random()*10000000000,true);
		    	earthPanel.fetchKml('<?php echo("$base_url");?>kml/black_earth.kmz',false);
		    	//earthPanel.kmlTreePanel.getRootNode().findChild('text','PoP Map').expand();
		    	//earthPanel.kmlTreePanel.getRootNode().findChild('text','Black Background').ui.toggleCheck();
		    }
		    

            // Add panels to browser viewport
            var viewport = new Ext.Viewport({
                layout: 'border',
                defaults: {
				    collapsible: true,
				    split: true,
				    autoScroll: true
				    //bodyStyle: 'padding:15px'
				},
                items: [ northPanel, controlPanel, earthPanel ]
            });
        

            // Build control panel
            earthPanel.on('earthLoaded', function(){

                // Display KMLs
                earthPanel.fetchKml('<?php echo("$full_url");?>',true);
                earthPanel.fetchKml('<?php echo("$base_url");?>kml/black_earth.kmz',false);

                // Add panels
                controlPanel.add(earthPanel.getKmlPanel());
                controlPanel.add(getGlobalsPanel());
                controlPanel.add(getColorPanel());
                controlPanel.add(earthPanel.getLocationPanel());
                controlPanel.add(earthPanel.getLayersPanel());
                controlPanel.add(earthPanel.getOptionsPanel());
                controlPanel.items.items[0].add(downloadPanel);
                controlPanel.doLayout();
                
               // remove the networkLink used for 'flyToView' effect 
                ge = earthPanel.earth;
                first_time = true;
				google.earth.addEventListener(ge.getView(), 'viewchangeend', function() {
					if(first_time){
						ge.getFeatures().removeChild(earthPanel.networkLink);
						first_time = false;
					}
				});
           		
           		// disable black background & expand the 'PoP Map' tree-view      
                first_time2 = true;
				google.earth.addEventListener(ge.getView(), 'viewchangebegin', function() {
					if(first_time2){
						earthPanel.kmlTreePanel.getRootNode().findChild('text','Black Background').ui.toggleCheck();
						//earthPanel.kmlTreePanel.getRootNode().item(0).expand();
						//earthPanel.kmlTreePanel.getRootNode().expandChildNodes();
						earthPanel.kmlTreePanel.getRootNode().findChild('text','PoP Map').expand();
						first_time2 = false;
					}
				});
				
	            //set a click listener that affects all placemarks
				google.earth.addEventListener(
				    ge.getGlobe(), 'click', function(event) {
				    var obj = event.getTarget();
				    if (obj.getType() == 'KmlPlacemark'){
				      event.preventDefault();
				      var placemark = obj;
				      var placemark_name = placemark.getName();
				      //get the full balloon html
				      var placemark_desc_active = placemark.getBalloonHtmlUnsafe();
				      //same as above, except with 'active' content like JS stripped out
				      //var placemark_desc = placemark.getBalloonHtml();
				      //create new balloon with rendered content
				      var balloon = ge.createHtmlStringBalloon('');
				      balloon.setFeature(placemark);
				      //balloon.setMaxWidth(300);
				      //balloon.setContentString('<h3>' + placemark_name + '</h3>' + placemark_desc_active);
				      balloon.setContentString(placemark_desc_active);
				      if(placemark_name.toLowerCase().indexOf('edge')!=-1){
				      	balloon.setMinWidth(800);
				      	balloon.setMinHeight(400);
				      }
				      ge.setBalloon(balloon);
				    }
				});
				
            });
        });

    </script>
</head>
<body>
	<div id="northPanel"><?php include("header.php") ?></div>
    <div id="westPanel"></div>
    <div id="eastPanel"></div>
    <div id="downloadPanel">
    	  <?php
            if(file_exists($filename)) {
              echo ("<p><a href=\"$filename\">click here</a> to download kml file</p>");
            } else {
              echo( "If you can see this, something is wrong..." );
            }
        ?>
    </div>
</body>
</html>