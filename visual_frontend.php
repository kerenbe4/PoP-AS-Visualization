<?php
require_once("verify.php");	
require_once("bin/idgen.php");
require_once('bin/userData.php');
require_once('bin/color.php');

$queryID = isset($_REQUEST["QID"])? $_REQUEST["QID"] : '2df5efc4b99b9486e245a49f6400a90f';

include_once("bin/kml_render_globals.php");

$idg = new idGen($queryID);
$filename='queries/'.$idg->getDirName().'/result.kmz';
$base_url = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI'])."/";
$full_url = $base_url.$filename."?".rand(0,10000000000);

$key = (stristr(PHP_OS, 'WIN'))? "ABQIAAAAMYziiEA_p76rk0jQj-KuSxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxRpJH3_NoHEcRApDRZWpWCuTc7H3A": 
								 "ABQIAAAAMYziiEA_p76rk0jQj-KuSxS9xmgvb7l5q_xOSCi2ySYKrO4w4RQ3kwRCrSDgo72ydEml2SNVnGd8DQ";
								 
$COLOR_LIST = array();
$user_data = $session->user_data;
$filename = 'data/ASN_color.data';
if(file_exists($filename)){
	$file_handle = fopen($filename,"r") or die("can't open ".$filename."\n");
	$str = fgets($file_handle);
	$COLOR_LIST =  unserialize($str);
	fclose($file_handle);
}
if(isset($user_data[$queryID]) && isset($user_data[$queryID]['asn-color'])){
	foreach($user_data[$queryID]['asn-color'] as $asn=>$color){
		$COLOR_LIST['asn'][$asn] = $color;
		$COLOR_LIST['color'][$color->web_format()] = $asn;
	}
}
if(isset($user_data['global']) && isset($user_data['global']['asn-color'])){
	foreach($user_data['global']['asn-color'] as $asn=>$color){
		$COLOR_LIST['asn'][$asn] = $color;
		$COLOR_LIST['color'][$color->web_format()] = $asn;
	}
}	

?>
<html>
<head>
    <title>DIMES PoP/AS Visual FrontEnd</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
	<!-- includes for extJS 2.2 -->
	<link rel="stylesheet" type="text/css" href="js/ext-2.2/resources/css/ext-all.css">
    <script type="text/javascript" src="js/ext-2.2/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="js/ext-2.2/ext-all.js"></script>
    
    <!-- includes for jQuery -->
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script type="text/javascript" src="js/loadData.js"></script>
    
    <!-- includes for ExtJS GEarthPanel Plugin -->
    <link rel="stylesheet" type="text/css" href="css/Ext.ux.GEarthPanel-1.1.css" />
    <link rel="stylesheet" type="text/css" href="css/visual.css" />
    <script type="text/javascript" src="http://www.google.com/jsapi?key=<?php echo $key ?>"></script>
  	<script type="text/javascript" src="js/Ext.ux.GEarthPanel-1.1.js"></script>
  	
  	<!-- includes for ExtJS ColorPicker Widget -->
  	<script language="javascript" src="js/ColorPicker/ux/widgets/ColorPicker.js"></script>
    <script language="javascript" src="js/ColorPicker/ux/widgets/form/ColorPickerField.js"></script>
    <link rel="stylesheet" type="text/css" href="js/ColorPicker/resources/color-picker.ux.css" />
	
    <script type="text/javascript">
    	
    	var ge;
        google.load("earth", "1");
        google.load("maps", "2.xx");
        
        Ext.onReady(function(){
        	
        	/*
        	Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
        		expires: new Date(new Date().getTime()+(1000*60*60*24*365)), //1 year from now
        	}));
        	*/


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
                width: 280,
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

            var downloadPanel = new Ext.Panel({
				contentEl: 'downloadPanel',
				border: false,
				defaultType: 'panel'	
			});
			
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
			
			/*
			var globalsPanel = new Ext.Panel({
				title: 'Kml Renderer Options',
				autoLoad: {url: 'extjs_form.php',scripts:true, params: 'queryID=<?php echo $queryID; ?>'}
			});
			*/
		    
			
			function getASNColorPanel(){

				var myData = [
					<?php
						foreach($COLOR_LIST['asn'] as $asn=>$color){
							echo "['".$asn."','#".$color->web_format()."'],";
						}
					?>
			        //['174','#0A9F50'],
			        //['209','#E9111F'],
			    ];
				
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
		    	
		    	// create the data store
			    var store = new Ext.data.SimpleStore({
			        fields: [
			           {name: 'asn', type: 'int'},
			           {name: 'color' },
			        ]
			    });
			    store.loadData(myData);
			    
			    // create the Grid
			    var gridPanel = new Ext.grid.GridPanel({
			    	title: 'ASN Color Management',
			    	autoHeight:true,
			    	//enableColumnResize: false,
			        //width: 280,
			    	hideHeaders: true,
			    	//titleCollapse: true,
			    	//border: false,
			        store: store,
			        columns: [
			            {id:'asn',header: "ASN", width: 30, align: 'center', sortable: true, dataIndex: 'asn'},
			            {id:'color', header: "Color", width: 75, align: 'center', sortable: true, renderer: renderColorPicker, dataIndex: 'color'},
			        ],
			        stripeRows: true,
			        viewConfig: {
			         	autoFill:true,
			            forceFit:true
			        },
			        autoExpandColumn:'color',
			        //autoExpandMin: 100,
			        // TODO: add buttons array - button with submit function
			        bbar: [{
			        	text: 'Submit',
			        	handler: function() {
			    			 var sm = store.getModifiedRecords();
			    			 var temp = '';
			    			 for (i=0; i<=sm.length-1; i++) {
			    			 	var div = (i==0)? '' : '|';
			        			temp = temp + div + sm[i].get('asn') + ':' + sm[i].get('color');
			    			 }
			    			 //alert(temp);
			    			 Ext.Ajax.request({
			        		 	url: 'process_data.php',
			        			method: 'POST',
			        			params: 'color_string=' + temp,
			        			waitMsg: 'rendering kml...',
	                        	waitTitle: 'kml-render-engine',
			        			success: function(obj) {
			            			var resp = obj.responseText;
			            			if (resp != 0) {
			                			Ext.MessageBox.alert('Success', resp + ' Processed');
			            			} else {
			                			Ext.MessageBox.alert('Failed', 'No Processed');
			            			}
			        			}
			    			});
			        	}
			        }]
			    });
			    
			    //return colorsPanel;
			    return gridPanel;
			}
			
			function  getGlobalsPanel(){
		
				var globalParams = {
					MIN_LINE_WIDTH: 	 { value: <?php echo MIN_LINE_WIDTH; ?>, 	type: 'number',		name: 'Min Edge Line Width'},
					MAX_LINE_WIDTH: 	 { value: <?php echo MAX_LINE_WIDTH; ?>,	type: 'number',		name: 'Max Edge Line Width'},
					TRANSPARENCY: 	 	 { value: <?php echo TRANSPARENCY; ?>,	type: 'number',		name: 'Line Transparency'},
					INITIAL_ALTITUDE:	 { value: <?php echo INITIAL_ALTITUDE; ?>,	type: 'number',		name: 'Initial ASN Altitude'},
					ALTITUDE_DELTA:		 { value: <?php echo ALTITUDE_DELTA; ?>,	type: 'number',		name: 'ASN Altitude Delta'},
					STDEV_THRESHOLD: 	 { value: <?php echo STDEV_THRESHOLD; ?>,	type: 'number',		name: 'Standard Deviation Threshold'},
					DRAW_CIRCLES:		 { value: <?php echo DRAW_CIRCLES; ?>,	type: 'checkbox',	name: 'PoP Location Convergence Radiuses'},
					INTER_CON: 			 { value: <?php echo INTER_CON; ?>,	type: 'checkbox',	name: 'Inter-Connectivity'},
					INTRA_CON: 			 { value: <?php echo INTRA_CON; ?>,	type: 'checkbox',	name: 'Intra-Connectivity'},
					CONNECTED_POPS_ONLY: { value: <?php echo CONNECTED_POPS_ONLY; ?>,	type: 'checkbox',	name: 'Connected PoPs only'},
					ASN_EMBEDDED_IN_PLACEMARK: { value: <?php echo ASN_EMBEDDED_IN_PLACEMARK; ?>,	type: 'checkbox',	name: 'Embed ASN In Placemarks'},
					//BLACK_BACKGROUND: 	 { value: <?php echo BLACK_BACKGROUND; ?>,	type: 'checkbox',	name: 'Black Background'},
					USE_COLOR_PICKER: 	 { value: <?php echo USE_COLOR_PICKER; ?>,	type: 'hidden',	name: 'Web-Safe Color-Picking'}
			   };
			
			
			    var items = [];
			    items.push({
			    	xtype: 'hidden',
			    	name: 'queryID',
			    	value: '<?php echo $queryID; ?>'
			    });
			    items.push({
			    	xtype: 'hidden',
			    	name: 'submitted',
			    	value: 'yes'
			    });
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
			               	minValue: 0,
			                //maxValue: 100,
			                //allowDecimals: true,
			                //decimalPrecision: 1,
			                name: param
						});
					}
			    }
			    
	
		        // Create FormPanel with all layers
		        var globalsPanel = new Ext.FormPanel({
		            title: 'Kml Renderer Options',
			        border: false,
			        autoHeight: true,
			        autoWidth: true,
			        formId: 'globalsForm',
			        labelWidth: 120,
			        //stateful: true,
			        url: 'render_kml.php',
			        defaults: {
			        	//stateful: true,
			        	//stateEvents: ['change'],
			        	//getState: function() {return this.getValue();},
    					//applyState: function(state) {this.setValue(state);}
			        },
			        items: items
		        });
		        
		        
		        var submit = globalsPanel.addButton({
		        	text: 'Submit',
	                handler: function() {
	                    var form = globalsPanel.getForm(); // get the basic form
	                    if (form.isValid()) { // make sure the form contains valid data before submitting
	                        form.submit({
	                        	waitMsg: 'rendering kml...',
	                        	waitTitle: 'kml-render-engine',
	                            success: function(form, action) {
	                               reloadKML();
	                            },
	                            failure: function(form, action) {
	                                Ext.Msg.alert('Failed', action.result.msg);
	                            }
	                        });
	                    } else { // display error alert if the data is invalid
	                        Ext.Msg.alert('Invalid Data', 'Please correct form errors.')
	                    }
	                }
		        });
		        
		        return globalsPanel;
		    }
		    
		    function reloadKML(){
		    	earthPanel.resetKml();
		    	earthPanel.fetchKml('<?php echo("$full_url");?>?'+Math.random()*10000000000);
		    }
		    

            // Add panels to browser viewport
            var viewport = new Ext.Viewport({
                layout: 'border',
                defaults: {
				    collapsible: true,
				    split: true,
				    //bodyStyle: 'padding:15px'
				},
                items: [ northPanel, controlPanel, earthPanel ]
            });

            // Build control panel
            earthPanel.on('earthLoaded', function(){

                // Display KMLs
                earthPanel.fetchKml('<?php echo("$full_url");?>');

                // Add panels
                controlPanel.add(earthPanel.getKmlPanel());
                controlPanel.add(getGlobalsPanel());
                controlPanel.add(getASNColorPanel());
                controlPanel.add(earthPanel.getLocationPanel());
                controlPanel.add(earthPanel.getLayersPanel());
                controlPanel.add(earthPanel.getOptionsPanel());
                controlPanel.items.items[0].add(downloadPanel);
                controlPanel.doLayout();

                ge = earthPanel.earth;
                first_time = true;
				google.earth.addEventListener(ge.getView(), 'viewchangeend', function() {
					//if(first_time){
						ge.getFeatures().removeChild(earthPanel.networkLink);
						earthPanel.kmlTreePanel.getRootNode().item(0).expand();
						first_time = false; 
					//}
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