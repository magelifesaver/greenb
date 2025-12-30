(function( $ ) {
	'use strict';

	var wb_stn=
	{
		bringTopTmr:null,
		archives_offset:0,
		init:function()
		{ 
			this.loadNotes();
			this.regActions();
			this.loadArchives();			
		},
		fireAjax:function(post_data,elm)
		{
			post_data.action='wb_stn';
	        post_data.security=wb_stn_data.nonces.main;
			$.ajax({
				url:wb_stn_data.ajax_url,
				data:post_data,
				type:'post',
				dataType:'json',
				success:function(data)
				{
					if(data.response===true)
					{
						if(post_data.wb_stn_action=='get_notes')
						{ 
							$('body').append(data.data);
							$('.wb_stn_note[data-wb_stn_id="0"]').hide().addClass('wb_stn_dummy_html');
							wb_stn.setInnerHeight();
						}
						else if(post_data.wb_stn_action=='create_note')
						{
							if(elm!==null)
							{
								elm.attr('data-wb_stn_id',data.id_wb_stn_notes);
							}
						}else if(post_data.wb_stn_action=='toggle_archive')
						{
							var note_html = data.data;
							if($('.wb_stn_note[data-wb_stn_id="0"]').length)
							{
								var temp_elm = $('<div />').html(data.data);
								temp_elm.find('.wb_stn_note[data-wb_stn_id="0"]').remove();
								note_html = temp_elm.html();
							}

							$('body').append(note_html);
							wb_stn.setInnerHeight();
							wb_stn.loadArchives(wb_stn.archives_offset); /* refresh */

						}else if(post_data.wb_stn_action=='get_archives') 
						{
							wb_stn.removeLoader($('.wb_stn_archives'));
							$('.wb_stn_archives').html(data.data);
						}
					}
				},
				error:function()
				{
					if(post_data.wb_stn_action == 'get_archives') 
					{
						wb_stn.removeLoader($('.wb_stn_archives'));
					}
				}
			});
		},
		setInnerHeight:function()
		{
			$('.wb_stn_note').each(function(){
				var h=$(this).height()-49;
				$(this).find('.wb_stn_note_body_inner').height(h);
			});
		},
		deleteNote:function(elm)
		{
			if(confirm(wb_stn_data.labels.areyousure))
			{
				var id=elm.attr('data-wb_stn_id');
				elm.remove();
				var data={wb_stn_action:'delete_note',id_wb_stn_notes:id};
				this.fireAjax(data,null);
			}
		},
		createNew:function(elm,is_dup)
		{
			var is_dup=is_dup ? is_dup : 0;
			if(!elm)
			{
				elm=$('.wb_stn_dummy_html');
			}
			if(elm.length==0){
				return false;
			}
			var pos=elm.position();
			var pos_l=pos.left;
			pos_l=pos_l<elm.width() ? pos_l+elm.width() : pos_l-elm.width();
			var new_elm=elm.clone().appendTo("body").removeClass('wb_stn_dummy_html').show().attr("data-wb_stn_id",0).css({'left':pos_l});
			if(is_dup==0)
			{
				new_elm.find('.wb_stn_note_body_inner').html('');
			}
			this.bringTop(new_elm,false);
			wb_stn.hideDropDownMenu(elm);
			wb_stn.hideDropDownMenu(new_elm);

			var pos=new_elm.position();
			var data={
				wb_stn_action:'create_note',
				id_wb_stn_notes:0,
				content:new_elm.find('.wb_stn_note_body_inner').html(),
				width:parseInt(new_elm.width()),
				height:parseInt(new_elm.height()),
				postop:parseInt(pos.top),
				posleft:parseInt(pos.left),
				z_index:parseInt(new_elm.css('z-index')),
				font_size:parseInt(new_elm.css('font-size')),
				font_family:new_elm.attr('data-wb_stn_font'),
				theme:new_elm.attr('data-wb_stn_theme'),
        global_note: confirm('Make this note global?') ? 1 : 0,
			};
			this.fireAjax(data,new_elm);
		},
		setZindex:function()
		{
			var note_data={};
			$('.wb_stn_note').each(function(){
				var id=parseInt($(this).attr('data-wb_stn_id'));
				if(id>0) /* exclude dummy and new elements */
				{
					note_data[id]=$(this).attr('data-wb_stn_zindex');
				}
			});
			var data={
				wb_stn_action:'set_zindex',
				note_data:note_data,
			}
			this.fireAjax(data,null);
		},
		saveNote:function(elm,data)
		{
			var id=elm.attr('data-wb_stn_id');
			data.id_wb_stn_notes=id;
            data.wb_stn_action='save_note';
			this.fireAjax(data,elm);
		},
		regActions:function()
		{
			$(document).on('mouseover','.wb_stn_note',function(){
				if(!$(this).data("wb_stn_drag_init"))
				{
            		$(this).data("wb_stn_drag_init", true).draggable({
						/* containment:"parent", */
						/* scroll: false, */
						handle:".wb_stn_note_hd",
						snap: true,
						start:function(event,ui)
						{
							/* $('body').css({'overflow-x':'hidden'}); */
						},
						drag:function(event,ui)
						{
							$(event.target).attr('data-wb_stn_left',Math.round(ui.position.left));
							$(event.target).attr('data-wb_stn_top',Math.round(ui.position.top));
						},
						stop:function(event,ui)
						{
							var ww=parseInt($(window).width());
							var wh=parseInt($(window).height());
							var ew=parseInt($(event.target).width());
							var eh=parseInt($(event.target).height());
							var maxl=ww-(ew/2);
							var maxt=wh-30;
							if(ui.position.top<0)
							{
								$(event.target).css('top',0);
								ui.position.top=0;
							}
							if(ui.position.top>maxt)
							{
								$(event.target).css('top',maxt);
								ui.position.top=maxt;
							}
							if(ui.position.left>maxl)
							{
								$(event.target).css('left',maxl);
								ui.position.left=maxl;
							}
							if(ui.position.left<0)
							{
								$(event.target).css('left',0);
								ui.position.left=0;
							}
							/* $('body').css({'overflow-x':'auto'}); */
							var data={posleft:Math.round(ui.position.left),postop:Math.round(ui.position.top)};
							wb_stn.saveNote($(event.target),data);
						}
					});
        		}
        		if(!$(this).data("wb_stn_resize_init"))
				{
					$(this).resizable().resizable('destroy');
            		$(this).data("wb_stn_resize_init", true).resizable({
						/* containment:"body", */
						alsoResize:$(this).find(".wb_stn_note_body_inner"),
						minHeight:150,
						minWidth:180,
						handles:"all",
						resize:function(event,ui)
						{ 
							$(event.target).attr('data-wb_stn_left',Math.round(ui.position.left));
							$(event.target).attr('data-wb_stn_top',Math.round(ui.position.top));
							$(event.target).attr('data-wb_stn_width',Math.round(ui.size.width));
							$(event.target).attr('data-wb_stn_height',Math.round(ui.size.height));
						},
						stop:function(event,ui)
						{
							var data={posleft:Math.round(ui.position.left),postop:Math.round(ui.position.top),width:Math.round(ui.size.width),height:Math.round(ui.size.height)};
							wb_stn.saveNote($(event.target),data);
						}
					});
        		}
			});
			$(document).on('click','.wb_stn_note .wb_stn_note_remove',function(){
				var elm=$(this).parents('.wb_stn_note');
				wb_stn.deleteNote(elm);
			});
			$(document).on('click','.wb_stn_note .wb_stn_note_options_menu',function(){
				var drp_menu=$(this).siblings('.wb_stn_note_menu_dropdown');
				if(drp_menu.is(':visible'))
				{
					drp_menu.hide();
				}else
				{
					drp_menu.show();
					drp_menu.find('.wb_stn_note_options_sub_menu').hide();	
				}
			});
			$(document).on('click','.wb_stn_note [data-wb_stn_note_options_sub]',function(){
				var trgt_clss='.'+$(this).attr('data-wb_stn_note_options_sub');
				var sub_menus=$(this).parents('.wb_stn_note_menu_dropdown').find('.wb_stn_note_options_sub_menu').not(trgt_clss);
				sub_menus.hide();
				var cr_submnu=$(this).parents('.wb_stn_note_menu_dropdown').find(trgt_clss);
				if(cr_submnu.is(':visible'))
				{
					cr_submnu.hide();
				}else
				{
					cr_submnu.show();
				}
			});
			$(document).on('click','.wb_stn_note .wb_stn_note_options_sub_menu_theme li',function(){
				var elm=$(this).parents('.wb_stn_note');
				wb_stn.setTheme(elm,$(this).attr('data-wb_stn_val'));
				wb_stn.hideDropDownMenu(elm);
			});
			$(document).on('click','.wb_stn_note .wb_stn_note_options_sub_menu_font li',function(){
				var elm=$(this).parents('.wb_stn_note');
				wb_stn.setFont(elm,$(this).attr('data-wb_stn_val'));
				wb_stn.hideDropDownMenu(elm);
			});

			$(document).on('mousedown','.wb_stn_note',function(){
				var elm=$(this);
				/* clearTimeout(wb_stn.bringTopTmr);
				wb_stn.bringTopTmr=setTimeout(function(){ */
					wb_stn.bringTop(elm,true);
				/* },200);	*/
			});

			$(document).on('input','.wb_stn_note .wb_stn_note_body_inner',function(){
				var c_elm=$(this);
				clearTimeout(wb_stn.contentUpdateTmr);
				wb_stn.contentUpdateTmr=setTimeout(function(){
					var elm=c_elm.parents('.wb_stn_note');
					var data={content:c_elm.html()};
					wb_stn.saveNote(elm,data);
				},1000);	
			});

			$(document).on('click','.wb_stn_note .wb_stn_new, .wb_stn_note .wb_stn_duplicate',function(){
				var elm=$(this).parents('.wb_stn_note');
				var is_dup=$(this).hasClass('wb_stn_duplicate') ? 1 : 0;
				wb_stn.createNew(elm,is_dup);
			});

			$('.wb_stn_new').off('click').on('click', function(){
				var elm=$('.wb_stn_dummy_html');
				wb_stn.createNew(elm,0);
			});

			$('#wp-admin-bar-wb_stn_admin_bar_menu_toggle, .wb_stn_toggle').on('click', function(){
				wb_stn.toggle();
			});

			$('body, body *').on('click', function(e){
		    	var drp_menu=$('.wb_stn_note_menu_dropdown');
		    	if(drp_menu.is(':visible'))
		    	{
		    		if($(e.target).hasClass('wb_stn_note_menu_dropdown')===false && $(e.target).parents('.wb_stn_note_menu_dropdown').length==0)
			    	{
			    		drp_menu.hide();
			    	}
		    	}
		    });

		    $(document).on('click','.wb_stn_unarchive_btn', function(){
		    	var elm=$(this).parents('.wb_stn_archive');
				wb_stn.toggleArchive(elm, 1);
		    });

		    $(document).on('click','.wb_stn_archive_btn', function(){
		    	var elm=$(this).parents('.wb_stn_note');
		    	wb_stn.hideDropDownMenu(elm);
				wb_stn.toggleArchive(elm, 2);
		    });

		    $(document).on('click','.wb_stn_pagination_btn', function(){
		    	var offset = $(this).attr('data-offset');
				wb_stn.loadArchives(offset);
		    });
		},
		hideDropDownMenu:function(elm)
		{
			elm.find('.wb_stn_note_menu_dropdown').hide();
		},
		bringTop:function(elm,save_it)
		{
			var elm_zindex=parseInt(elm.css('z-index'));
			var top_zindex=elm_zindex;
			var low_zindex=0;
			$('.wb_stn_note').each(function(){
				var zindx=$(this).css('z-index');
				if(low_zindex==0)
				{
					low_zindex=zindx;
				}else
				{
					if(zindx<low_zindex)
					{
						low_zindex=zindx;
					}
				}
				if(zindx>=elm_zindex)
				{
					if(zindx>top_zindex)
					{
						top_zindex=zindx;
					}
					zindx=zindx-1;
					$(this).css('z-index',zindx).attr('data-wb_stn_zindex',zindx);
				}			
			});
			if(low_zindex==top_zindex)
			{
				top_zindex=(top_zindex==elm_zindex ? top_zindex+1 : top_zindex);
			}
			elm.css('z-index',top_zindex).attr('data-wb_stn_zindex',top_zindex);
			if(elm_zindex==top_zindex){
				return false; /* no need to fire save ajax  */
			}
			if(save_it)
			{
				this.setZindex();
			}
		},
		toggle:function()
		{
			var state=0;
			if($('.wb_stn_note').is(':visible'))
			{
				$('.wb_stn_note').hide();
			}else
			{
				state=1;
				$('.wb_stn_note').not('.wb_stn_dummy_html').show();
			}
			var data={
				wb_stn_action:'toggle_notes',
				id_wb_stn_notes:0,
				state:state,
			};
			this.fireAjax(data,null);
		},
		setTheme:function(elm,vl)
		{
			var cr_theme=elm.attr('data-wb_stn_theme');
			elm.removeClass(cr_theme).addClass(vl);
			elm.attr('data-wb_stn_theme',vl);
			
			var data={theme:vl};
			wb_stn.saveNote(elm,data);
		},
		setFont:function(elm,vl)
		{
			var cr_theme=elm.attr('data-wb_stn_font');
			elm.removeClass(cr_theme).addClass(vl);
			elm.attr('data-wb_stn_font',vl);
			
			var data={font_family:vl};
			wb_stn.saveNote(elm,data);
		},
		loadNotes:function()
		{
			var data={
	            wb_stn_action:'get_notes'
	        };
			this.fireAjax(data,null);
		},
		toggleArchive:function(elm, status)
		{
			var id=elm.attr('data-wb_stn_id');
			if(elm.hasClass('wb_stn_note'))
			{
				var top_menu_pos=$('#wp-admin-bar-wb_stn_admin_bar_menu').position();
				elm.animate({'top':top_menu_pos.top, 'left':top_menu_pos.left, 'width':0, 'height':0, 'opacity':0}, 500, function(){
					elm.remove();
				});

			}else
			{
				elm.remove();
				if($('.wb_stn_archives .wb_stn_archive').length==0) /* if last item */
				{
					$('.wb_stn_archives').prepend('<div class="wb_stn_no_items">'+wb_stn_data.labels.no_data_to_display+'</div>');
				}
			}

			var data={'wb_stn_action':'toggle_archive', 'status':status, 'id_wb_stn_notes':id};
			this.fireAjax(data, null);
			this.setLoader($('.wb_stn_archives'));
		},
		loadArchives:function(offset)
		{
			if($('.wb_stn_archives').length)
			{
				this.setLoader($('.wb_stn_archives'));
				this.archives_offset = (offset ? offset : 0);
				var data={
		            wb_stn_action:'get_archives',
		            wb_stn_offset:this.archives_offset
		        };
				this.fireAjax(data, null);
			}
		},
		setLoader:function(elm)
		{
			if(elm.length)
			{
				elm.html('<div class="wb_stn_loader"></div>');
			}
		},
		removeLoader:function(elm)
		{	
			if(elm.length)
			{
				elm.find('.wb_stn_loader').remove();
			}
		}
	};
	
	$(function() {
		wb_stn.init();
	});

})( jQuery );