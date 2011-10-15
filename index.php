<?php
	include_once("bin/load_config.php");
	include_once("verify.php");	
?>


<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Pop/AS Visualizer</title>
        <!-- <script src="http://code.jquery.com/jquery-latest.js"></script> -->
        <script src="js/jquery-1.6.2.min.js" type="text/javascript"></script>
        <script type="text/javascript" src="js/loadData.js"></script>
        <link rel="stylesheet" href="css/jquery.multiselect2side.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="css/visual.css" type="text/css" media="screen" />
		<script type="text/javascript" src="js/jquery.multiselect2side.js" ></script>
		<script type="text/javascript" src="js/jquery-blink.js" ></script>
          
          <script type="text/javascript">
			
			 // Test connection to blade 
             function testConnection() {
             	//$.preLoadImages("images/ajax-loader.gif");
             	$.preLoadImages("images/icon_OK.png");
             	$("#testStatus").remove();
				$('#blade').after('<img id="testStatus" class="validator" src="images/ajax-loader.gif"/>');
             	$('#AS :input, #formButtons :input').not("#blade").prop('disabled',true);
             	//$("#stage2,#stage3,#formButtons").addClass('formGrayOut');        	                
                $.post("query_backend.php", { func: "testConnection", blade: $("#blade").val() },
                    function(data){
                    		 $("#sendQueryDBStatus").remove();
                    		 $("#testStatus").hide();
                             var result = data.result;
                             if(data!=null){
	                             if (data.type =="ERROR"){
	                             	//$('#AS #blade').removeClass('formGrayOut').prop('disabled',false);
	                             	$("#resetForm").after('<p id="sendQueryDBStatus" class="sendQueryValidator" style="color:red">'+result+'</p>');
	                             } else {
	                             	$("#testStatus").replaceWith('<img id="testStatus" class="validator" src="images/icon_OK.png"/>');
	                             	$("#testStatus").show();
	                             	$('#AS :input, #formButtons :input').prop('disabled',false);
	                             }
	                          }                            
                    }, "json");	
             }
             
             function getTables(){
             	if ($("#blade").val()!="" && $("#year").val()!="" && $("#week").val()!=""){
             		$('#AS :input').prop('disabled',true);
             		
					$('#button-wrap-1').html('<p><img src="images/ajax-loader.gif"/></p>');                                   
					
                    $.post("query_backend.php", {func: "showTables", blade: $("#blade").val(),
                     year: $("#year").val(),week: $("#week").val()},
                    function(data){
                    	
	                	$('#button-wrap-1').html('<input id="getTables" type="button" value="Get tables" style="margin-left: 20px; margin-top: 10px"/>');
	                    $("#getTables").click(function() {
		                	getTables();                    
		                });                        			
                         
						 if (data.type=="GOOD"){  
						 	
						 	 $("#Edge").html("");
							 $("#PoP").html("");
							 $("#popIP").html("");                  	
						 
						     if (data.edge!= ""){
						     	var allEdges = data.edge;	                                               
						     	var edges = allEdges.split(" ");
						     	for(i = 0; i < edges.length; i++){								
									$("#Edge").append("<option>" + edges[i] + "</option> "); 									
								 }
								 disableStage(2,false);
								 $('#getTables').prop('disabled',true);
						     }else {$("#Edge").append("<option>No tables available</option> ");}	                         	                                                 
						     
						     if (data.pop!=""){
						     	var allPops = data.pop;
						     	var pops = allPops.split(" ");
						     	for(i = 0; i < pops.length; i++){								
									$("#PoP").append("<option>" + pops[i] + "</option> ");
								 }
						     }else {$("#PoP").append("<option>No tables available</option> ");}
						                              
						     if (data.popIP!=""){
						     	var allPops2 = data.popIP;
						     	var pops2 = allPops2.split(" ");
						     	for(i = 0; i < pops2.length; i++){								
									$("#popIP").append("<option>" + pops2[i] + "</option> ");
								 }
						     }else {$("#popIP").append("<option>No tables available</option> ");}	                        
							
						}else{
							$("#Edge").html("<option>Connection error</option> ");
							$("#PoP").html("<option>Connection error</option> ");
							$("#popIP").html("<option>Connection error</option> ");
						}
					}, "json");	
                }
             } 
             
             
             /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
             
             var queryID;
             
             function myQueriesBindUpdateEvent(){
            	$("#My_queries").bind('update', function(e, data) {
             		$(".checkStatus").each(function(index) {
         				var queryID = $(this).attr('id');
         				$("#"+queryID).html('<p class="blink">checking...</p>');
         			});
         			globalData.pq_running=true;
    				//pool_pq_status(data.pid);
            		globalData.interval = setInterval( "pool_pq_status("+data.pid+")" , 5000 );  // pool with 5-sec intervals
            	}); 
             }
            
             function updateTable(){
             	$('#queryTable').load('index.php #queryTable').fadeIn("slow");
             	myQueriesBindUpdateEvent();          		
             }
             
             var error_counter;
             function stageOne(){
             	error_counter = 0;
             	//$.preLoadImages("images/ajax-loader.gif");
             	$("#sendQueryStatus").remove();
             	if(!$("#searchable").val()){
             		$("#resetForm").after('<p id="sendQueryStatus" class="sendQueryValidator" style="color:red">complete the form first!</p>');
             		return;
             	}
				$("#resetForm").after('<img id="sendQueryStatus" class="validator" src="images/ajax-loader.gif"/>');
             	$.post("query_backend.php", {func: "sendQuery", stage:1, blade: $("#blade").val() ,
                         edge: $("#Edge").val(), pop: $("#PoP").val(), popIP: $("#popIP").val(), year: $("#year").val(), week: $("#week").val(), username: <?php echo '"'.$username.'"'?>, as: $("#searchable").val() },
                         function(data){
                         	if (data==null || data.type=="ERROR"){
                         		$("#My_queries_info").html('<p style="color:red">ERROR - The query did not run successfuly</p>');
                         		$("#sendQueryStatus").remove();
                         		if(data!=null) $("#My_queries_info").append('<p style="color:red">'+data.result+'</p>');
                         	}else if(data.type=="ALL_COMPLETE"){
                         		$("#My_queries_info").html('<p style="color:red; text-align:center">'+data.result+'</p>');
                         		$("#sendQueryStatus").remove();
                         	}else if(data.type=="GOOD"){
                         		queryID=data.queryID;
	                     		$("#sendQueryStatus").remove();
	                     		globalData.pq_running=false;
								clearInterval(globalData.interval);
	                     		updateTable();
	                     		setTimeout("run_pq_script()",1000);
                         	} else {
                         		if(data.type!="STAGE1_COMPLETE"){
                         			$("#My_queries_info").html('<p style="color:red; text-align:center">ASSERTION ERROR</p>');
                         			$("#sendQueryStatus").remove();
                         		} else {
                         			stageTwo();
                         		}
                         	}                         	                        				                         	                         	                         
                        }
                         ,"json");     
             }
                 
             function stageTwo(){
             	$.post("query_backend.php", {func: "sendQuery", stage:2, blade: $("#blade").val() ,
                         edge: $("#Edge").val(), pop: $("#PoP").val(), popIP: $("#popIP").val(), year: $("#year").val(),week: $("#week").val(), username: <?php echo '"'.$username.'"'?>, as: $("#searchable").val() },
                         function(data){
                         	if (data==null || data.type=="ERROR"){
                         		$("#My_queries_info").html('<p style="color:red">ERROR - The query did not run successfuly</p>');
                         		if(data!=null) $("#My_queries_info").append('<p style="color:red">'+data.result+'</p>');
                         		$("#sendQueryStatus").remove();
                         	} else {
                         		if(data.type!="STAGE2_COMPLETE"){
                         			$("#My_queries_info").html('<p style="color:red">ASSERTION ERROR</p>');
                         			$("#sendQueryStatus").remove();
                         		} else {
	                     			stageThree();
                         		}
                         	}                         	                        				                         	                         	                         
                        }
                         ,"json");
             }
             
             function stageThree(resendQuery,queryID)
             {
             	if(error_counter>=3) {
             		$("#My_queries_info").html('<p style="color:red">ERROR - The query did not run successfuly</p>');
             		$("#sendQueryStatus").remove();
             		if(resendQuery) $('#queryTable').html('');
             		return;
             	}
             	var properties1 =  {func: "resendQuery", query: queryID, stage:3,  username: <?php echo '"'.$username.'"'?> };
             	var properties2 = {func: "sendQuery", stage:3, blade: $("#blade").val() ,
                         edge: $("#Edge").val(), pop: $("#PoP").val(), popIP: $("#popIP").val(), year: $("#year").val(),week: $("#week").val(), username: <?php echo '"'.$username.'"'?>, as: $("#searchable").val() };
             	$.post("query_backend.php", ((resendQuery)? properties1 : properties2) ,
                         function(data){
                         	if (data==null || data.type=="ERROR"){
                         		//$("#My_queries_info").html('<p style="color:red">ERROR - The query did not run successfuly</p>');
                         		//if(data!=null) $("#My_queries_info").append('<p style="color:red">'+data.result+'</p>');
                         		error_counter++;
                         		stageThree(resendQuery,queryID);
                         	} else {
                         		if(data.type!="GOOD"){
                         			$("#My_queries_info").html('<p style="color:red">ASSERTION ERROR</p>');
                         			$("#sendQueryStatus").remove();
                         		} else {
                         			queryID=data.queryID;
	                     			$("#sendQueryStatus").remove();
	                     			globalData.pq_running=false;
									clearInterval(globalData.interval);
	                     			updateTable();
	                     			setTimeout("run_pq_script()",1000);
                         		}
                         	}                         	                        				                         	                         	                         
                        }
                         ,"json");
             }
             
             ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			function resendQuery(queryID){
				error_counter = 0;
				//$.preLoadImages("images/ajax-loader.gif");
             	$('#queryTable').html('<p><img src="images/ajax-loader.gif"/></p>');  				
             	$.post("query_backend.php", {func: "resendQuery", query: queryID, stage:2,  username: <?php echo '"'.$username.'"'?> },
             	function(data){
		     		if (data==null || data.type=="ERROR"){
                 		$("#My_queries_info").html('<p style="color:red">ERROR - The query did not run successfuly</p>');
                 		if(data!=null) $("#My_queries_info").append('<p style="color:red">'+data.result+'</p>');
                 		$("#sendQueryStatus").remove();
                 	} else {
                 		if(data.type!="STAGE2_COMPLETE"){
                 			$("#My_queries_info").html('<p style="color:red">ASSERTION ERROR</p>');
                 			$("#sendQueryStatus").remove();
                 		} else {
                 			stageThree(true,queryID);
                 		}
                 	}                         
             	}
             	,"json");
			}
			
			
            function abort(queryID){             	
             	//$('#queryTable').fadeOut('fast');
             	//$.preLoadImages("images/ajax-loader.gif");
             	$('#queryTable').html('<p><img src="images/ajax-loader.gif"/></p>');  				
             	$.post("user_query_managment.php", {func: "abort", query: queryID, username: <?php echo '"'.$username.'"'?> },
             	function(data){
             		if(data.type=="GOOD"){
             			updateTable();
             		}
             		if (data.type =="ERROR")
                     	{alert(data.result);}
             	}
             	,"json");
                                                  	
             }
            
            function pool_pq_status(pid){
            	if(globalData.pq_running==true){
            		$.post("query_backend.php", { func: "pq-status", blade: globalData.blade },
					function(data,textStatus){
						if(data!=null) {
							if(data.type == "ERROR"){
								globalData.pq_running=false;
								clearInterval(globalData.interval);
								//alert(data.result);
								console.log(data.result);
								$("#My_queries_info").html('<p style="color:red;text-align:center;">ERROR - update-status procedure ended unexpectedly.</br>look in js console for details..</p>');
								$(".checkStatus").each(function(index) {
			         				var queryID = $(this).attr('id');
			         				$("#"+queryID).html('error');
			         			});
								return;
							}
							if(data.type == "RUNNING_STATUS"){
								var msg = {
									db_ready: 'fetching XML...',
									xml_done: 'rendering KML...',
									xml_fail: 'error during xml fetch',
									complete: 'completed',
									kml_fail: 'error during kml rendering',
									error: 'error'
								};
								var status = eval('('+data.result+')');
								for(qid in status){
									$("#"+qid).html(msg[jQuery.trim(status[qid])]);
								}
								return;
							}
							if(data.type == "FINISHED"){
								globalData.pq_running=false;
								clearInterval(globalData.interval);
								updateTable();
								return;
							}
						}
					}, "json");						
            	} 	
            }
            
            function getASList(){
            	//$.preLoadImages("images/ajax-loader.gif");
            	$('#AS :input').prop('disabled',true);
            	
				$('#button-wrap-2').html('<p><img src="images/ajax-loader.gif"/></p>');                                                           
                $.post("query_backend.php", {func: "getASlist", blade: $("#blade").val(), edge: $("#Edge").val() , pop: $("#PoP").val()},
                function(data){
                	
					$('#button-wrap-2').html('<input id="getAS" type="button" value="Get AS list!" style="margin-left: 20px; margin-top: 10px"/>');
					$("#getAS").click(function() {
                    	getASList();         
                    });
                     
					if (data.type=="GOOD"){
						if (data.result==""){
					 		$("#stage3").html('<p style="font-size: 12px; color: black">No AS to show for your query.</p>');
						}else{
							$("#stage3").html("<br></br><select multiple='multiple' id='searchable' name='searchable[]'></select>");
									                        	                        			
	                        var allAS = data.result;
							var AS = allAS.split("*");	
							 							 	                         	                         
	                        for(i = 0; i < AS.length; i++){
	                         	var tmp = AS[i].split(" ");								
								$("#searchable").append('<option value="' + tmp[0] + '">' + AS[i] + "</option> "); 								
							}
							
							$('#searchable').multiselect2side({'search': 'Search: '});
							
							disableStage(3,false);
							$('#getAS').prop('disabled',true);
						}
						
					}else{                 	                        																	 
						 $("#stage3").html('<p style="font-size: 12px; color: red">Connection error - can not reach server.</p>');
					}	                         
                }, "json");
            }
            
            
            function disableStage(stage,disable){
            	$("#stage"+stage+" :input").prop('disabled',disable);
            }
            
            function resetForm(){
            	
 				$('#AS :input').not("#stage1 :input").prop('disabled',true);
 				disableStage(1,false); 				           
     			
     			$(':input','#AS')
				 .not(':button, :submit, :reset, :hidden')
				 .val('')
				 .removeAttr('checked')
				 .removeAttr('selected');
 
     			$("#week").html('<option selected="selected" value="">Select week</option>');
     			
     			$("#Edge").html('<option selected="selected" value="">Select edge table</option>');
				$("#PoP").html('<option selected="selected" value="">Select PoP table</option>');
				$("#popIP").html('<option selected="selected" value="">Select PoP IP table</option>');
				
				$("#stage3").html('<p style="font-size: 10px; color: gray">After clicking the list will apear.</p>');
            	$(".sendQueryValidator").each(function(index) {
     				$(this).remove();
     			});   
            }
            
            $(document).ready(function() {
            	$('#AS :input').not("#stage1 :input").prop('disabled',true);
			});
			
            
            $(document).ready(function() {
            	$("#My_queries").ready(function() {
            		myQueriesBindUpdateEvent();
            	});
			});
			
			
			/*                      
            $(document).ready(function() {
            		testConnection();                   
                    $("#blade").change(function() {
                    	if ($("#blade").val() != ""){
                       		testConnection();
                       	}
                    });
            });
            */
            
                                                          
            //get possible tables 
            $(document).ready(function() {              	 
                $("#getTables").click(function() {
                	getTables();                    
                });              
            });
            
          
            $(document).ready(function() {              	 
                $("#year").change(function() {                	
                	if ($("#year").val()!=""){
                		$.post("query_backend.php", {func: "getWeeks", blade: $("#blade").val(),year: $("#year").val()},
                        function(data){
                        	if (data.type=="GOOD"){
                        		$("#week").html('');	
								var weeks = data.weeks;		            
								if (weeks!=null){
		                         	for(i = 1; i < weeks.length; i++){
		                    			var selected_str = (i==weeks.length-1)? 'selected="selected"':''; 							
										$("#week").append('<option '+selected_str+'>' + weeks[i] + '</option> '); 									
									 }
	                         	}else {$("#week").append("<option>No weeks available</option> ");}
                         	}else {$("#week").html("<option>Connection error</option> ");}		                        	                        			
	                         
                        }, "json");	
                	}					                	            
                });              
            });
                     
                                    
            $(document).ready(function() {
                    $("#getAS").click(function() {
                    	getASList();         
                    });                    
            });
            
            
            $().ready(function() {
				$('#searchable').multiselect2side({'search': 'Search: '});
			});
           
            
            // send the query to server
            $(document).ready(function() {
                    $("#sendQuery").click(function() {                                                           
                        stageOne();                                               
                    });
            });
            
            // send the query to server
            $(document).ready(function() {
                    $("#resetForm").click(function() {                                                           
                        resetForm();                                               
                    });
            });
                                 
            </script>                  
    </head>
    
    <body>        
        
        <div id="container">
			
			<?php include("header.php") ?>
						                       
            <div id="user-select" class="user-select">          
            	<h3 style="text-align:center; size:4; color:rgb(112,97,68); font-family: verdana,arial,sans-serif">Make a new query</h3>
                                                               
                <form id="AS" name="get AS list" style="font-size:14px;">                               
                    <div id="stage1">
	                    <p class="selection-header">Select blade</p>
	                	<div align="left" class="selection-text">Blade:                    
		                    <select id="blade">
		                    	<option value="">Select blade</option>
		                            <?php                                                          
		                            foreach($Blades as $blade)
	                                {
	                                    $name = $blade["@attributes"]["name"];
	                                    if($name!="" && $Blade_Map[$name]["db"]=="DIMES_DISTANCES"){
											if(isset($blade["@attributes"]["default"]) && ($blade["@attributes"]["default"] == "true")){
	                                            echo '<option selected="selected">'.$name.'</option>';
											} else {
												echo "<option>$name</option>";
											}
										}
	                                }
		                            ?>
		                    </select>
	                   </div>
	                   
	                    <p class="selection-header">Select date</p>       
	                    <div align="left" class="selection-text">Year  :                       
	                        <select id="year" >
	                            <option value="">Select year</option>
	                            <?php
	                            
	                            	$xml = simplexml_load_file("xml/weeks.xml");
									$result = $xml->xpath('/DATA/YEAR/year');					
									if($result!=FALSE)
									{
										var_dump($result);
										foreach($result as $i=>$value){
											echo "<option>".$value."</option>";
										}					
									}                                                   
	                            ?>                            
	                        </select>
	                    </div>
	
	                     <div align="left" class="selection-text">Week:
	                        <select id="week">                               
	                            <option selected="selected" value="">Select week</option>                                                  
	                        </select>
	                    </div>
                    
	                    <div id="button-wrap-1">
	                    	<input id="getTables" type="button" value="Get tables" style="margin-left: 20px; margin-top: 10px"/>
	                    </div>
                    </div>
                                                           
                    <p class="selection-header">Select table</p>                                       
               		
               		<div id="stage2">
	               		<div align="left" class="selection-text">PoP IP:                       
	                        <select id="popIP" >
	                            <option value="">Select PoP IP table</option>                            
	                        </select>
	                    </div>
	
	               		<div align="left" class="selection-text">PoP  :                       
	                        <select id="PoP" >
	                            <option value="">Select PoP table</option>                            
	                        </select>
	                    </div>
	
	                     <div align="left" class="selection-text">Edge:
	                        <select id="Edge">                               
	                            <option selected="selected" value="">Select edge table</option>                            
	                        </select>
	                    </div>
               		
	               		
	               		<div id="button-wrap-2">
	                    	<input id="getAS" type="button" value="Get AS list!" style="margin-left: 20px; margin-top: 10px"/>
	                    </div>
					</div>
						                    
                    <div id="stage3">
                    	<p style="font-size: 10px; color: gray">After clicking the list will apear.</p>
                    </div>
                                                           
                </form>
                <div id="formButtons">
	                <input id="sendQuery" class="sendQuery" type="image" src="images/send-button.png"/>
	                <input id="resetForm" class="resetForm" value="Reset Form" type="button"/></br>
              	</div>
            </div>
            
            <div id="My_queries">
                <h3 style="size:4; color: rgb(112,97,68)">My queries</h3>                	
                <br></br>                

				<table id="queryTable" class="imagetable" style="alignment-baseline: central">				
				
				<?php
					echo "<tr>";
					echo "<th>QID</th><th>Year</th><th>Week</th><th>Tables</th><th>AS Count</th><th>Status</th><th>Delete</th>";
					echo "</tr>";
					$queries = simplexml_load_file("xml/query.xml");
					$result = $queries->xpath('/DATA/QUERY[users/user="'.$username.'"]');					
					if($result!=FALSE)
					{						
						foreach ($result as $i => $value) {												
							echo "<tr>";							
							echo "<td>".substr($result[$i]->queryID,-4)."</td>";
							echo"<td>".$result[$i]->year."</td>";
							echo"<td>".$result[$i]->week."</td>";
							echo"<td>".$result[$i]->EdgeTbl."</BR>".$result[$i]->PopTbl."</BR>".$result[$i]->PopLocTbl."</td>";
							echo"<td>".$result[$i]->ASnum."</td>";
							echo "<td>";
							if ($result[$i]->lastKnownStatus=="running"){
								echo '<div id="'.$result[$i]->queryID.'" class="checkStatus">running</div>';
							}elseif ($result[$i]->lastKnownStatus=="completed"){
								echo '<form method="get" action="visual_frontend.php" target="_blank"><input name="QID" type="hidden" value="'.$result[$i]->queryID.'"/><input type="submit" id=QstatusC value="Complete"/></form>';
							}elseif ($result[$i]->lastKnownStatus=="error"){
								echo '<button type="submit" onclick="resendQuery(this.value)" value="'.$result[$i]->queryID.'">RUN</button>';
								//echo 'error';													
							}else {
								echo 'unknown status';
							}
							echo "</td>" . '<td> <button type="submit" onclick="abort(this.value)" value="'.$result[$i]->queryID.'">X</button></td>';							
							// reload the page? if changing to "submit",  add: onsubmit="return false;" ?
							echo "</tr>";
						} 
					}
				?>																
				
				</table>
				<div id="My_queries_info"></div>                
            </div>
            
            <div class="footer">
                Copyright © 2011 <a href="http://www.netdimes.org/new/">DIMES</a>
            </div>
            
         </div>
    </body>
</html>