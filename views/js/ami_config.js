$(document).ready(function(){
    $("#mc_mini").change( function() {
        $('input[name="mini"]').val($("#mc_mini").val());
      });
    $("#mc_maxi").change( function() {
        $('input[name="maxi"]').val($("#mc_maxi").val());
      });
      
    $('#cm_form_submit_btn').hide();

    $.ajax({
        url: conf_url + '&action=getBasicConfigs'
    }).done(function(data){
        basicConfigs =  JSON.parse(data);
        $('#basic_config_table > tr:nth-child(1) > td:nth-child(2) > input').val(basicConfigs['api_key']);
        $('#basic_config_table > tr:nth-child(2) > td:nth-child(2) > input').val(basicConfigs['baseUrl']);
        $('#order_states_lst').val(basicConfigs['status_cmd']);
        $('#carriers_lst').val(basicConfigs['carrier']);
    }
    ).fail(function(xhr, textStatus, errorThrown) {
        console.log(errorThrown)
    });
    
    $.ajax({
        url: conf_url + '&action=getCarrierLst'
    })
    .done(function( data ) {
            console.log(data);
            $('#carriers_lst').html(data);
    })
    .fail(function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
    });
    $.ajax({
        url: conf_url + '&action=getOrderStates'
    })
    .done(function( data ) {
            console.log(data);
            $('#order_states_lst').html(data);
    })
    .fail(function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
    });

    $.ajax({
        url: conf_url + '&action=getModelCodes'
    })
    .done(function( data ) {
            console.log(data);
            $('#cm_lst').html(data);
    })
    .fail(function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
    });
    $.ajax({
        url: conf_url + '&action=getConfigHistory'
    })
    .done(function(data){
        $('#config_history_table').html(data);
        $('.deleteConfig').click(function(event){
            event.preventDefault();
            $.ajax({
                url: conf_url + '&action=deleteConfig&model_code=' + $(this).closest('tr').find('td > input[name="model_code"]').val()
            })
            .done(function(data){
                $(this).closest('tr').hide();
                $.notify('deleted !', 'success');
            })
            .fail(function(xhr, textStatus, errorThrown) {
                console.log(errorThrown)
            });
        });
        $('.editConfig').click(function(event){
            event.preventDefault();
            $.ajax({
                url: conf_url + '&action=editConfig&model_code=' + $(this).closest('tr').find('td > input[name="model_code"]').val()
            })
            .done(function(data){
                $('#config_history_panel').hide();
                data =  JSON.parse(data);
                $('#selectedMC').val(data['model_code']);
                $('#lst_articles').html(data['lst_articles']);
                $('#listStores').html(data['listStores']);
                $('#new_config_panel').show()
            })
            .fail(function(xhr, textStatus, errorThrown) {
                console.log(errorThrown)
            });
        });
    })
    .fail(function(xhr, textStatus, errorThrown) {
        console.log(errorThrown)
    });

    $('#new_config_panel').hide();
    $('#add_new_config').click(function(){
        $('#config_history_panel').hide();
        $('#config_history_table').html('');
        $('#new_config_panel').show();
        $('input[name="checkStores"]').prop("chekced", true);
        $('input[name="store"]').prop( "checked", true );
        $('#selectedMC').val('');
    });

    $('#cm_form_submit_btn').click(function(event){
        event.preventDefault();
        $.ajax({
            url: conf_url + '&action=getArticlesByMC&id_mc=' + $('#selectedMC').val()
        })
        .done(function( data ) {
                $('#lst_articles').html(data);
                $.ajax({
                    url: conf_url + '&action=getStores'
                })
                .done(function( data ) {
                        $('#listStores').html(data);
                        $('input[name="checkStores"]').change(function(){
                            if(this.value=='0'){
                                $('input[name="store"]').prop( "checked", false );
                            }else if(this.value=='1'){
                                $('input[name="store"]').prop( "checked", true );
                            }
                        });
                })
                .fail(function(xhr, textStatus, errorThrown) {
                    console.log(errorThrown)
                });
        })
        .fail(function(xhr, textStatus, errorThrown) {
            console.log(errorThrown);
        });
    });
    
    $('#submitOptionsBasicConfiguration').click(function(){
        var api_key = $('#basic_config_table > tr:nth-child(1) > td:nth-child(2) > input').val();
        var url = $('#basic_config_table > tr:nth-child(2) > td:nth-child(2) > input').val();
        var order_state = $('#order_states_lst option:selected').val();
        var carrier = $('#carriers_lst option:selected').val();
        var missing_values = ([api_key,url,order_state,carrier].some(el => el == ''))?true:false;
        if(!missing_values){
            $.ajax({
                url: conf_url + '&action=setBasicConfigs' + '&api_key=' + api_key + '&baseUrl=' + url + '&status_cmd=' + order_state + '&carrier=' + carrier
            })
            .done(function(data){
                console.log('inserted');
                $.notify("saved !","success");
            })
            .fail(function(xhr, textStatus, errorThrown) {
                console.log(errorThrown)
            });
        }
    });
    $('#submitOptionsconfiguration').click(function(){
        $('#config_history_panel').show();
        $('#new_config_panel').hide();
        var missing_values = false;
        json = [];
        var rows = $('#lst_articles > tr');
        rows.each(function(){
            if(($(this).find('td:nth-child(4) >input').val() !='0') || ($(this).find('td:nth-child(5) >input').val() !='0')){
                item = {};
                item['mc'] = $('#selectedMC').val();
                item['is_to_update'] = ($('#lst_articles > tr:nth-child(1) > td:nth-child(6) >input').val() =='')?false:true;
                item['id'] = $(this).find('td:nth-child(6) >input').val();
                item['article'] = $(this).find('td:nth-child(1)').text();
                item['mini'] = $(this).find('td:nth-child(4) >input').val();
                item['maxi'] = $(this).find('td:nth-child(5) >input').val();

                if((item['mini'] == '') || (item['maxi'] == '')){
                    item['mini'] = $('#mc_mini').val();
                    item['maxi'] = $('#mc_maxi').val();
                }

                if((item['mc'] == '')){
                    missing_values = true;
                    return false;
                }

                var targetStores = '';
                var stores = $('div.checkbox > label > input[name="store"]:checked');
                stores.each(function(){
                    targetStores += $(this).val() + '|';
                });
                item['stores'] = targetStores;
                json.push(item);
            }
        });

        if(!missing_values){
            var action = (item['is_to_update'])?'updateConfig':'setNewConfig';
            $.ajax({
                url: conf_url + '&action='+ action +'&articles_config=' + JSON.stringify(json)
            })
            .done(function(data){
                console.log('inserted');
                $.notify("saved !","success");
                $.ajax({
                    url: conf_url + '&action=getConfigHistory'
                })
                .done(function(data){
                    $('#config_history_table').html(data)
                })
                .fail(function(xhr, textStatus, errorThrown) {
                    console.log(errorThrown)
                });
                
            })
            .fail(function(xhr, textStatus, errorThrown) {
                console.log(errorThrown)
            });
        }
    });
});