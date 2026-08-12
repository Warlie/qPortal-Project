    /* adds a quersum function to Number */
    Number.prototype.quersumme = function(forceOneDigit)
    {
    var z = this.toString().split('');
    for (var i=0, quer=0; i < z.length; quer+=z[i++]-0); 
    if( forceOneDigit && quer > 9) 
      return quer.quersumme(forceOneDigit);

    return quer;
    }   

/**
 * This class handles the map for itself and its layers.
 * It is designed for the DWEE article page and contols just
 * dots, text and lines for the map.
 *
 * @author Stefan Wegerhoff
 * @version 1.0
 */
class OrgaControl {
	


		#json_journey = [];
		#counter = 1;
		#reduced_code = 0;
		#full_code = 0;
		#interval_seconds = 1000;
		#find_next_code = {"id" : -1, "date":"", "code": "", "location": "", "wayToGo": "", quests : "" , "status" : 0};
		#state_request = {"request" : null, "process_state" : 0, "idle" : false};
		#called_number = 0;
		#ask = function(request_response, arg_process, arg_request)
		{
			//console.debug(process, request);

			var req = 0;
			if(request_response.request == null)
				req = -1;
			else
			{
				req = request_response.request.readyState;

			}
			//request.request.
			return req == arg_request && (request_response.process_state == arg_process) && !request_response.idle;
			
		}

		/**
		constructor
		
		initialization for the behavior of the layers 
		and connection to display and language
		
		@param map the leaflet map
		@param measurement measurement for comparison
		*/
  	  constructor ()
  	  	{
  	  		const queryString = window.location.search;


const urlParams = new URLSearchParams(queryString);


			this.#called_number = urlParams.get('quest');

  	  	}
  	  	  	  	
  	  	
  	  	
  	  	/**
  	  	@return a json element to modify display
  	  	*/
  	  	show_table()
  	  	{
  	  		var items = this.#json_journey;
  	  		console.debug(items);
  	  		$(function() {
            		$('#overview tr').remove();
            $.each(items, function(i, item) {

            		$( "<tr class=\"table-body\" ><td><a href=\"?quest=" + item.id + "\" >" + item.date + "</a></td><td>" + item.code +  "</td></tr>").appendTo('#overview');
            });
        });
        	
  	  	}
  	  	
  	  	/**
  	  	@return a json element to modify display
  	  	*/
  	  	find_next_code()
  	  	{
  	  		var items = this.#json_journey;
  	  		var find_next = this.#find_next_code;
  	  		var found =false;
  	  		var called = this.#called_number;
  	  		this.#json_journey.forEach( function(item) 
  	  			{
  	  				console.debug(item, called);
            		if(item.id == called && !found)
            		{

           				find_next.id = item.id;
           				find_next.date = item.date;
           				find_next.location = item.location;
           				find_next.wayToGo = item.wayToGo;
           				find_next.state = parseInt(item.state, 10);
           				find_next.quests = item.quests;
            			found = true;
            			console.debug(find_next);
            		}
            	});

        	return found;
  	  	}  	  	
  	  	
  	  	control_function()
  	  	{
  	  		
  	  		
  	  		/*
  	  		0 - null :den Aktuellen Datensatz abfragen.
  	  		0 - 4 : Tabelle aktuallisieren 
  	  		1 - 4 : fehlenden Code finden (wenn gefunden 2 wenn nicht 5 )
  	  		2 - 4 : Modal oeffnen (wenn Code gültig und Antwort vorhanden 3 - 4 sonst 3 - X)
  	  		3 - 4 : Code senden (4 - 4)
  	  		4 - 4 : Neue Tabelle empfangen.
  	  		
  	  		
  	  		
  	  		
  	  		{ i:".service_orga" , function:"get.table" }
  	  		
  	  		if(
  	  			this.#state_request.request ==  &&
  	  			this.#state_request.process_state == 
  	  			)
  	  		this.#state_request.request
  	  		this.#state_request.process_state
  	  		this.#state_request.idle
  	  		*/
  	  		
  	  		if(this.#ask(this.#state_request, 0,-1))
  	  			{
  	  				console.debug("drin0,0");
  	  				this.set_request( { i:".service_orga" , function:"get.table" } ); // get.table is called at first

  	  			}
  	  		if(this.#ask(this.#state_request, 0,4))
  	  			{
  	  				this.#state_request.request = null;
  	  				this.#state_request.process_state = 1;
  	  				console.debug("drin0,1");
  	  				this.show_table(); /* writes dates and codes into a table */
  	  				this.#interval_seconds = 2000;


  	  			}
 	  		if(this.#ask(this.#state_request, 1,-1))
  	  			{
  	  				console.debug("drin1");
  	  				if(this.find_next_code()) /* finds next missing code in table */
  	  				  { 
  	  				  	  console.log("next day found");
  	  				  	  console.debug(this.#find_next_code);
  	  				  	  if(this.#find_next_code.state == 0)
  	  				  	  {
  	  				  	  	  console.log("it is a new day");
  	  				        this.#state_request.process_state = 2; /* next state (asking for missing code) */
  	  				      }
  	  				  	else
  	  				  	{
  	  				  		console.log("it is a current day");
  	  				  		this.#state_request.process_state = 5; /* next state (continues current day) */
  	  				  	}
  	  				  }
  	  			  	else
  	  			  	  this.#state_request.process_state = 7; /* next state (idle and request for new day) */

  	  			}
  	  		// 2 - 4 : Modal oeffnen (wenn Code gültig und Antwort vorhanden 3 - 4 sonst 5 - X)
 	  		if(this.#ask(this.#state_request, 2,-1))
  	  			{
  	  				//$("#entry-modal").modal();
  	  				console.debug("drin2");
  	  				this.#interval_seconds = 500;
  	  				this.#state_request.idle  = false;
  	  				//this.find_next_code();
  	  				//console.debug(this.#find_next_code);
  	  				this.#state_request.process_state = 3;
  	  			}
  	  		// 3 - 4 : Code senden (4 - 4)
  	  		if(this.#ask(this.#state_request, 3,-1))
  	  			{
  	  				this.#interval_seconds = 1000;
  	  				console.log("send code to server");
  	  				console.debug({ i:".service_orga" , function:"set.code", full_code: this.#full_code, location : ((this.#reduced_code % this.#find_next_code.location) + 1) , id: this.#find_next_code.id, wayToGo: this.#find_next_code.wayToGo });
  	  				this.set_request( { i:".service_orga" , function:"set.code", full_code: this.#full_code, location : ((this.#reduced_code % this.#find_next_code.location) + 1) , id: this.#find_next_code.id, wayToGo: this.#find_next_code.wayToGo } );

  	  				/*
  	  				this.#state_request.process_state = 0;
  	  				console.debug("drin5");
  	  				this.set_request( { i:".service_orga" , function:"get.table" } );
  	  				*/

  	  			}
  	  		if(this.#ask(this.#state_request, 3,4))
  	  			{
  	  				this.#interval_seconds = 500;
  	  				console.log("get response from code request");
  	  				this.#state_request.request = null;
  	  				this.#state_request.process_state = 20;
  	  				
 
  	  				/*
  	  				this.#state_request.process_state = 0;
  	  				console.debug("drin5");
  	  				this.set_request( { i:".service_orga" , function:"get.table" } );
  	  				*/

  	  			}
  	  			
  	  		if(this.#ask(this.#state_request, 20,-1))
  	  			{
  	  				this.#interval_seconds = 1000;
  	  				console.debug("ask server for new table");
  	  				  	  				
  	  				this.set_request( { i:".service_orga" , function:"get.table" } );
  	  				/*
  	  				this.#state_request.process_state = 0;
  	  				console.debug("drin5");
  	  				this.set_request( { i:".service_orga" , function:"get.table" } );
  	  				*/

  	  			}
  	  			
  	  		if(this.#ask(this.#state_request, 20,4))
  	  			{
  	  				this.#interval_seconds = 1000;
  	  				console.debug("show new table");
  	  				this.show_table(); /* writes dates and codes into a table */
  	  				this.#state_request.process_state = 4;
  	  				this.#state_request.request = null;
  	  				/*
  	  				this.#state_request.process_state = 0;
  	  				console.debug("drin5");
  	  				this.set_request( { i:".service_orga" , function:"get.table" } );
  	  				*/

  	  			}
  	  			
  	  			if(this.#ask(this.#state_request, 4,-1))
  	  			{
  	  				this.#interval_seconds = 2000;
  	  				console.debug("create link to first quest");
  	  				console.debug("drin4,-1");
  	  				var arg = this.get_data_of_main_Quest();
  	  				if(arg)
  	  					{
  	  						arg["i"] = ".service_orga";
  	  						arg["function"] = "set.story";
  	  						this.set_request( arg );
  	  						console.debug("set story table");
  	  					}

  	  				console.debug(this.#state_request.process_state);


  	  			}

  	  			if(this.#ask(this.#state_request, 4,4))
  	  			{
  	  				this.#interval_seconds = 2000;
  	  				console.debug("done");
  	  				this.#state_request.process_state = 5;
  	  				this.#state_request.request = null;

  	  		

  	  			}
  	  			
  	  		 if(this.#ask(this.#state_request, 5,-1))
  	  			{
  	  				this.#interval_seconds = 2000;
  	  				console.debug("set session");
  	  				console.debug(this.#find_next_code.id);
  	  				this.set_request( { i:".service_orga" , function:"set.session" , NightID : this.#find_next_code.id });
  	  				/*
  	  				var arg = this.get_data_of_main_Quest();
  	  				if(arg)
  	  					{
  	  						arg["i"] = ".service_orga";
  	  						arg["function"] = "set.story";
  	  						this.set_request( arg );
  	  					}
  	  				console.debug("drin5");NightID
  	  				*/

  	  			}
  	  			
	  		 if(this.#ask(this.#state_request, 5,4))
  	  			{
  	  				this.#interval_seconds = 2000;
  	  				console.debug("und rüber");
  	  				document.location.href = "index.php?i=.game_page";

  	  				/*
  	  				var arg = this.get_data_of_main_Quest();
  	  				if(arg)
  	  					{
  	  						arg["i"] = ".service_orga";
  	  						arg["function"] = "set.story";
  	  						this.set_request( arg );
  	  					}
  	  				console.debug("drin5");NightID
  	  				*/

  	  			}
  	  			
  	  		 if(this.#ask(this.#state_request, 6,-1))
  	  			{

  	  				console.debug("drin6");

  	  			}
  	  			
  	  		 if(this.#ask(this.#state_request, 7,-1))
  	  			{
  	  				this.#interval_seconds = 10000;
  	  				this.#state_request.process_state = 0;
  	  				this.#state_request.request = null;
  	  				console.debug("drin7");

  	  			}
  	  			
  	  			//console.debug(this.#json_journey);
  	  			
  	  	}
  	  	  	  	/**
  	  	@return a json element to modify display
  	  	*/
  	  	get interval_seconds(){return this.#interval_seconds;}
  	  	
  	  	/**
  	  	@param name name for a layer
  	  	@param choose the behavior of that layer (POINTLAYER, CONNECTIONLAYER)
  	  	@param <optional> geojson element 
  	  	@param z_index ZIndex order
  	  	*/
  	  	add_new_layer(name, type, z_index)
  	  	{

  	  	}
  	  	
  	  	/**
  	  	@param name call the layer
  	  	@param geojson element to load
  	  		<remote name="DBO.set_Column_config.tag_name" >questId</remote>
	<remote name="DBO.set_Column_config.field_name" >RelationsNightsToQuests.QuestFK</remote>
	<remote name="DBO.set_Column_config.content" >questId</remote>
  	  	
  	  	*/
  	  	get_data_of_main_Quest()
  	  	{
  	  		if(this.find_next_code())
  	  			{
  	  				console.log("next code found");
  	  				console.debug(this.#find_next_code);
  	  				return { "NightId" : this.#find_next_code.id, "questId" : this.#find_next_code.quests[0].id };
  	  			}
  	  			
  	  		alert("Diese Nachricht bedeutet schlimme Dinge. Das mir bitte melden, damit ich es beseitigen kann.");
  	  		return null;
  	  	}
  	  	
  	  	/**
  	  	@param name call the layer
  	  	@param geojson element to load
  	  	*/
  	  	get_code_for_journey(code)
  	  	{
  	  		code = 2002;
  	  		var check_count = code % 10;
  	  		var result = true;
  	  		this.#reduced_code = (code - check_count) / 10;
  	  		this.#full_code = code ;
  	  		
  	  		if(result = (this.#reduced_code.quersumme(true) == check_count))
  	  			this.#state_request.process_state = 3;

  	  		return (this.#reduced_code.quersumme(true) == check_count);		
  	  	}
  	  
  	  	init()
  	  	{
  	  		this.set_request({ i:".service_orga" , function:"get.table" });
  	  	}
  	  	
  	  	set_request( json_data /*  */ )
  	  	{
  	  	   this.#json_journey = [];
  	  	   var mytree = this.#json_journey;
  	  	   var readystate = this.#state_request;
  	  	   this.#state_request.idle  = true;
  	  		$(document).ready(function () {
        	readystate.request =($.ajax({
            type: "POST",
            url: "index.php",
            data: json_data,
            cache: false,
            dataType: "xml",
            success: function(xml) {
            	var quests = [];

                $(xml).find('day').each(function(){
                	
                	$(this).find('quest').each(function()
                		{
                			quests.push({ "id" : $(this).attr( "id" ), "QuestTypeNo" : $(this).find("typeNo").text()});
                		});
                		
                	//var myDate = (new Date($(this).find("date").text())).toLocaleDateString("de-DE");	
                	mytree.push({ "date": $(this).find("date").text(),
                	"code": $(this).find("code").text(), 
                	"id": $(this).attr( "id" ), 
                	"location": $(this).attr( "location" ), 
                	"wayToGo": $(this).attr( "wayToGo" ), 
                	"state": $(this).attr( "state" ),
                	"quests" : quests
                	});
                	quests = [];
                	
                	
                    
                });
                readystate.idle = false;

                
            }, error: function (request, error) {
        console.error("AJAX Call Error: " + error);
        console.debug(request);
    }
        }));
    });
  	  		/*
    		var xmlRequest = $.ajax({
    		method: "POST",
    		url: "index.php",
    		data: { i:".service_orga" , function:"get.table" }
    		});

    		var data = xmlRequest.responseXML;
*/
    		//console.debug(mytree);


  	  		
  	  	}
  	  	
  	  	/**
  	  	@param name call the layer
  	  	@param geojson element to load
  	  	*/
  	  	onLoad()
  	  	{ 
  	  		var my = this;
  	  		var request = this.#state_request;
  	  		//setTimeout(this.control_function(), 5000);
    		//this.control_function();
  	  		//this.interval = setInterval(this.control_function(), 2000);
  	  		//this.init();
  	  		//$("#entry-modal").modal()
  	  		/*
  	  		$('#modal_close').click(function() {
  	  		
  	  			if(!my.get_code_for_journey($("#codeForJourney").val()))
  	  				{
  	  					$("#modal_label").text("Mit dem Code stimmt etwas nicht. Er muss aus dem Kalender kommen.");
  	  				}

  	  			request.idle  = false;
  	  		});*/
  	  		test();
  	  		
  	  	}
  	  	

  	  	

  	}

var orga = new OrgaControl();

function test(){
	orga.control_function();

    setTimeout(test, orga.interval_seconds);
}


/*
$(document).ready(function(){
    test();
});
*/

//var interval = setInterval(orga.control_function(), 2000);
/*
var xmlRequest = $.ajax({
  method: "POST",
  url: "index.php",
  data: { i:".service_orga" , function:"get.table", name: "John", location: "Boston" }

  });
 console.debug(xmlRequest);
 console.debug($.ajax({
  method: "POST",
  url: "index.php",
  data: { i:".service_orga" , function:"set.code", name: "John", location: "Boston" }

  }));
 console.debug($.ajax({
  method: "POST",
  url: "index.php",
  data: { i:".service_orga" , function:"get.story", name: "John", location: "Boston" }

  }));
 */
