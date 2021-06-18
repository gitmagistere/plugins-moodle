// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript to handle wordcloud display.
 *
 * @module     mod_wordcloud
 * @package    mod_wordcloud
 * @copyright  2021 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery','core/str'], function($,str) {
	var wordclouds = [];
	var delaystep = 2000;
	var mindelay = 2000;
	var maxdelay = 60000;
	var svgminheight = 350;
	var svgmaxheight = 600;
	var apiurl = '';
	var fill = d3.scale.category20();
	
	function showWordcloud(cmid,selector,api_url,editor){
		apiurl = api_url;
		
		wordclouds[cmid] = new wordCloud(cmid,selector,$(selector).width(),$(selector).width()*0.56,editor);
	}
	
	class wordCloud {
		
		constructor(cmid, _selector, width, height, editor) {
		
			this.width = width;
			this.height = height>svgmaxheight?svgmaxheight:(height<svgminheight?svgminheight:height);
			
			this.displaymod;
			this.cmid = cmid;
			this.selector = _selector;
			this.parent = $(_selector).parent();
			
			this.editing = editor;
			this.isediting = false;
			this.editingword = '';
			
			this.simtimer;
			this.resizetimer;
			this.updatetimer;
			this.updatedelay = mindelay;
			this.lastupdate = 0;
			this.words = [];
			this.wordsrender = [];
			
			this.wcwidth = $(this.selector).parents('#region-main-box').width();
			this.borderwidth = this.wcwidth - $(this.selector).width();
			
		    this.weight = 0;
			this.currentgroupid = -1;
		    
		  //Construct the word cloud's SVG element
			this.svg = d3.select(this.selector).append("svg")
		    	.attr("width", this.width)
		        .attr("height", this.height)
		        .append("g")
		        .attr("transform", "translate("+(this.width/2)+","+(this.height/2)+")");
		    
			var svg = this.svg;
			
			if ($('#fitem_id_word_1').length > 0){
				this.switch_display('f');
			}else{
				if (this.parent.find('.wc_groupselector').length > 0) {
					this.changegroup(this.parent.find('.wc_groupselector').val());
				}else{
					this.changegroup(0);
				}
			}
			
			this.listeners();
		}
		
		listeners () {
			
			if (this.parent.find('.wc_groupselector').length){
				
				this.parent.find('.wc_groupselector').on('change', (function() {
					var select = this.parent.find('.wc_groupselector');
					this.changegroup(select.val());
				}).bind(this));
			}

			$( window ).resize((function() {
				if (this.displaymod=='c') {
					if (this.resizetimer != undefined){
						clearTimeout(this.resizetimer);
					}
					this.resizetimer = setTimeout((this.resize).bind(this),500);
				}
			}).bind(this));

			if (this.editing) {
				d3.select(this.selector.split(' ')[0]+' .wc_exportpng').on("click",this.exportPNG.bind(this));

				this.parent.on('click','.wc_exportdata',(function(){
					this.exportData();
				}).bind(this));
				
				this.parent.on('click','.wc_addword',(function(){
					this.addWord(this);
				}).bind(this));
				
				this.parent.on('keypress','input[name=wcaddword]',(function(e){
					var key = e.which;
					if(key == 13) {
						 $(this.parent).find('.wc_addword').click();
						 return false;  
				    }
				}).bind(this));
				
		    	$(this.selector).on('click', 'svg text', (function(event){
		    		if (!this.isediting) {
		    			this.loadWordEdit($(event.target).text())
		    		}
		    	}).bind(this));
		    	
		    	this.parent.on('keypress','input[name=wceditword]',(function(e){
					var key = e.which;
					if(key == 13) {
						 $(this.parent).find('.wc_updateword').click();
						 return false;  
				    }
				}).bind(this));
		    	
		    	this.parent.on('input','input[name=wceditword]',(function(e){
					if (this.isediting) {
		    			if (this.simtimer != undefined){
							clearTimeout(this.resizetimer);
						}
						this.simtimer = setTimeout((this.simUpdateWord).bind(this),500);
		    		}
				}).bind(this));
		    	
		    	$(this.parent).on('click', '.wc_updateword', (function(event){
		    		if (this.isediting) {
		    			this.updateWord();
		    		}
		    	}).bind(this));
		    	
		    	$(this.parent).on('click', '.wc_removeword', (function(event){
		    		if (this.isediting) {
		    			this.removeWord();
		    			this.closeWordEdit();
		    		}
		    	}).bind(this));

		    	this.parent.on('click', '.wc_closeedit', (function(event){
		    		if (this.isediting) {
		    			this.closeWordEdit();
		    		}
		    	}).bind(this));
		    	
		    }
		}
	    
	    //Draw the word cloud
	    draw(words) {
	        var cloud = this.svg.selectAll("g text").data(words, function(d) { return d.text; })
	        
	        //Entering words
	        cloud.enter()
	            .append("text")
	            .style("font-family", "Impact")
	            .style("fill", function(d, i) { return fill(Math.random()); })
	            .attr("text-anchor", "middle")
	            .attr('font-size', 1)
	            .text(function(d) { return d.text; });
	        
	        //Entering and existing words
	        cloud
	            .transition()
	                .duration(600)
	                .style("font-size", function(d) { return d.size + "px"; })
	                .attr("transform", function(d) {
	                    return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
	                })
	                .style("fill-opacity", 1);

	        //Exiting words
	        cloud.exit()
	            .transition()
	                .duration(200)
	                .style('fill-opacity', 1e-6)
	                .attr('font-size', 1)
	                .remove();
	    }

        update_svg() {
        	this.wordsrender = JSON.parse(JSON.stringify(this.words));
        	
            var cloud = d3.layout.cloud().size([this.width, this.height])
                .words(this.wordsrender)
                .padding(3)
                .rotate(function() { return (~~(Math.random() * 2) * 90)-45; })
                //.rotate(function() { return (~~(Math.random() * 2) * 90); })
                //.rotate(function() { return (~~(Math.random() * 2) * 45); })
                .font("Impact")
                .fontSize(function(d) { return d.size; })
                .on("end", this.draw.bind(this))
                .start();
        }
        
        resize(){
        	if (this.wcwidth != $(this.selector).parents('#region-main-box').width()) {
        		this.wcwidth = $(this.selector).parents('#region-main-box').width();
        		this.width = $(this.selector).parents('#region-main-box').width()-this.borderwidth;
        		this.height = this.width*0.56;
        		
        		if (this.height > svgmaxheight) {
        			this.height = svgmaxheight;
        		}else if (this.height < svgminheight) {
        			this.height = svgminheight;
        		}
	        	
	        	$(this.selector).find("svg").attr("width", this.width)
		        .attr("height", this.height).find("g").attr("transform", "translate("+(this.width/2)+","+(this.height/2)+")");;
		    	
		        this.update_svg();
        	}
	    }
	    
	    changegroup(groupid) {
			if (this.currentgroupid != groupid) {
				this.currentgroupid = groupid;
				this.lastupdate = 0;
				this.words = [];
				this.wordsrender = [];
				var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?id='+this.cmid+'&g='+groupid;
				window.history.replaceState({path:newurl},document.title,newurl);
				this.update_wordcloud();
			}
		}
		
	    displayaddform() {
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid": this.cmid, "action":"getaddword", "groupid":this.currentgroupid}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		
	            	}else{
	            		
	            	}
	            }).bind(this),
	            error: function (error) {
	            }
	        });
		}
	    
	    switch_display(mod) {
	    	if (this.displaymod == mod){
				if (mod == 'c') {
					if (this.editing) {
						//$('.wc_tools').show();
					}else{
						$('.wc_tools').hide();
					}
				}
				return;
			}
	    	if (mod == 'f') {
	    		this.displaymod = 'f';
	    		if ($('.wordcloudcontainer').is(':visible')) {
        			$('.wordcloudcontainer').hide();
        		}
	    		if (this.editing) {
	    			$('.wc_tools').hide();
	    		}
        		if (!$('#wcform').is(':visible')) {
        			$('#wcform').show();
        		}
	    	}else{
	    		this.displaymod = 'c';
	    		if ($('#wcform').is(':visible')) {
        			$('#wcform').html('');
        			$('#wcform').hide();
        		}
    			if (!$('.wordcloudcontainer').is(':visible')) {
        			$('.wordcloudcontainer').show();
        		}
    			if (this.editing) {
    				$('.wc_tools').show();
    			}	
    		}
	    }
		
		update_wordcloud(force=false) {
			if (!force && this.isediting){return;}
			if (this.lastupdate == undefined){
				this.lastupdate = 0;
			}
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid": this.cmid, "action":"getdata", "groupid":this.currentgroupid, "lastupdate":this.lastupdate}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		if (response.mod=='c') {
		            		if (response.noupdate==true) {
		            			this.updatedelay = this.updatedelay+delaystep;
		            		}else{
		            			if (this.parent.find('.wc_participation').length) {
		            				if (response.subs == 0) {
		            					str.get_string('nosubmition','mod_wordcloud').then((value)=>{ this.parent.find('.wc_participation_count').text(value); });
		            				}else if (response.subs == 1) {
		            					str.get_string('onesubmition','mod_wordcloud',response.subs).then((value)=>{this.parent.find('.wc_participation_count').text(value);})
		            				}else{
		            					str.get_string('multi_submition','mod_wordcloud',response.subs).then((value)=>{this.parent.find('.wc_participation_count').text(value);})
		            				}
		            			}
		            			this.updatedelay = delaystep;
		            			this.lastupdate = response.timemodified;
								this.editing = response.editor;
		            			this.words = response.words;
		            			this.switch_display('c');
		            			this.update_svg();
		            			if (this.words.length == 0){
		            				$('.wc_empty').show();
		            			}else{
		            				$('.wc_empty').hide();
		            			}
		            		}
			    			if (this.updatetimer != undefined){
			    				clearTimeout(this.updatetimer);
			    			}
			    			if (this.updatedelay < maxdelay) {
			    				this.updatetimer = setTimeout((function(){this.update_wordcloud();}).bind(this),this.updatedelay);
			    			}
	            		}else{
	            			this.switch_display('f');
	            			$('#wcform').html(response.form);
	            		}
	            	}else{
	            		
	            	}
	            	
	            	
	            }).bind(this),
	            error: function (error) {
	            }
	        });
		}
		
		exportPNG() {
		    d3.event.preventDefault();
		    //var fill = d3.scale.category20();
		    var t = document.createElement("canvas")
		      , e = t.getContext("2d");
		    t.width = this.width,
		    t.height = this.height,
		    e.translate(this.width >> 1, this.height >> 1),
		    //e.scale(scale, scale),
		    this.wordsrender.forEach(function(t, n) {
		        e.save(),
		        e.translate(t.x, t.y),
		        e.rotate(t.rotate * Math.PI / 180),
		        e.textAlign = "center",
		        e.fillStyle = fill(t.text.toLowerCase()),
		        e.font = t.size + "px " + t.font,
		        e.fillText(t.text, 0, 0),
		        e.restore()
		    });
		    var a = document.createElement("a");
		    a.href = t.toDataURL("image/png").replace("image/png", "image/octet-stream");
		    a.download = "export.png";
		    a.click();
		}
		
		exportData() {
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid":this.cmid, "action":"exportdata", "groupid":this.currentgroupid}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		var a = document.createElement('a');
	            		if (window.URL && window.Blob && ('download' in a) && window.atob) {
	            		    var blob = base64ToBlob(response.data, 'text/octet-stream');
	            		    var url = window.URL.createObjectURL(blob);
	            		    a.href = url;
	            		    a.download = 'export.csv';
	            		    a.click();
	            		    window.URL.revokeObjectURL(url);
	            		}
            			
	            	}else{
	            		
	            	}
	            }).bind(this),
	            error: function (error) {
	            }
	        });
		}
		
		addWord() {
			$(this.parent).find('input[name="wcaddword"]').prop("disabled",true);
			$(this.parent).find('.wc_addword').prop("disabled",true);
			var word = $(this.parent).find('input[name="wcaddword"]').val();
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid":this.cmid, "action":"addword", "word":word, "groupid":this.currentgroupid}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		this.update_wordcloud();
	            	}else{
	            	}
	            	$(this.parent).find('input[name="wcaddword"]').prop("disabled",false);
	    			$(this.parent).find('.wc_addword').prop("disabled",false);
	    			$(this.parent).find('input[name="wcaddword"]').val('');
	    			$(this.parent).find('input[name="wcaddword"]').focus();
	            }).bind(this),
	            error: (function (error) {
	            	$(this.parent).find('input[name="wcaddword"]').prop("disabled",false);
	    			$(this.parent).find('.wc_addword').prop("disabled",false);
	    			$(this.parent).find('input[name="wcaddword"]').val('');
	    			$(this.parent).find('input[name="wcaddword"]').focus();
	            }).bind(this)
	        });
		}
		
		loadWordEdit(word) {
			this.isediting = true;
			this.editingword = word;
			$(this.selector).addClass('locked');
			$("#updateword_fusion_warn").hide();
			this.parent.find('.wc_groupselector').prop('disabled', true);
			
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid":this.cmid, "action":"getwordinfo", "word":word, "groupid":this.currentgroupid}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		this.weight = response.weight;
	            		this.parent.find('input[name=wceditword]').val(response.word);
	            		this.parent.find('.wc_weight span').text(response.weight);
	            		
	            		var ul = this.parent.find('.wc_users ul');
	            		ul.html('');
	            		
	            		$(response.users).each(function(idx,val){
	            			ul.append('<li>'+val+'</li>');
	            		});
	            		
	            		if ( this.parent.find('.wc_editor_word').is(":hidden") ) {
	            			this.parent.find('.wc_tools').slideUp(500);
	            			this.parent.find('.wc_editor_word').slideDown(500);
	            		}
	            		
	            	}else{
	            	}
	            }).bind(this),
	            error: function (error) {
	            }
	        });
		}
		
		closeWordEdit() {
			this.isediting = false;
			this.editingword = '';
    		this.parent.find('input[name=wceditword]').val('');
    		this.parent.find('.wc_weight span').text('');
    		
    		var ul = this.parent.find('.wc_users ul');
    		ul.html('');
    		
    		this.parent.find('.wc_tools').slideDown(500);
    		this.parent.find('.wc_editor_word').slideUp(500);
    		$(this.selector).removeClass('locked');
    		this.parent.find('.wc_groupselector').prop('disabled', false);
		}
		
		updateWord() {
			var newword = this.parent.find('input[name=wceditword]').val();
			
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid":this.cmid, "action":"updateword", "word":this.editingword, "newword":newword, "groupid":this.currentgroupid}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		
	            		this.lastupdate=0;
	            		this.update_wordcloud(true);
	            		this.closeWordEdit();
	            	}else{
	            	}
	            }).bind(this),
	            error: function (error) {
	            }
	        });
		}
		
		simUpdateWord() {
			var newword = this.parent.find('input[name=wceditword]').val();
			
			if (this.editingword == newword) {
				$("#updateword_fusion_warn").hide();
				this.parent.find('.wc_weight span').text(this.weight);
			}else{
				$.ajax({
		            type: 'POST',
		            url: apiurl,
		            data: JSON.stringify({"cmid":this.cmid, "action":"simupdateword", "word":this.editingword, "newword":newword, "groupid":this.currentgroupid}),
		            dataType: 'json',
		            success: (function (response) {
		            	if (response.error==false) {
		            		console.log(response.subs);
		            		if (response.fusion){
		            			$("#updateword_fusion_warn").show();
		            			this.parent.find('.wc_weight span').text(""+response.subs+" (valeur actuelle : "+this.weight+")");
		            		}else{
		            			$("#updateword_fusion_warn").hide();
		            			this.parent.find('.wc_weight span').text(this.weight);
		            		}
		            	}else{
		            	}
		            }).bind(this),
		            error: function (error) {
		            }
		        });
			}
		}
		
		removeWord() {
			$.ajax({
	            type: 'POST',
	            url: apiurl,
	            data: JSON.stringify({"cmid":this.cmid, "action":"removeword", "word":this.editingword, "groupid":this.currentgroupid}),
	            dataType: 'json',
	            success: (function (response) {
	            	if (response.error==false) {
	            		
	            		this.lastupdate=0;
	            		this.update_wordcloud();
	            	}else{
	            	}
	            }).bind(this),
	            error: function (error) {
	            }
	        });
		}
	}
	
	function base64ToBlob(base64, mimetype, slicesize) {
	    if (!window.atob || !window.Uint8Array) {
	        return null;
	    }
	    mimetype = mimetype || '';
	    slicesize = slicesize || 512;
	    var bytechars = atob(base64);
	    var bytearrays = [];
	    for (var offset = 0; offset < bytechars.length; offset += slicesize) {
	        var slice = bytechars.slice(offset, offset + slicesize);
	        var bytenums = new Array(slice.length);
	        for (var i = 0; i < slice.length; i++) {
	            bytenums[i] = slice.charCodeAt(i);
	        }
	        var bytearray = new Uint8Array(bytenums);
	        bytearrays[bytearrays.length] = bytearray;
	    }
	    return new Blob(bytearrays, {type: mimetype});
	};
	
	return {
		showWordcloud : function(cmid,selector, apiurl, editor){
        	showWordcloud(cmid, selector, apiurl, editor);
        }
    };
});
