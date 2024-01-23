$(document).ready(function(){
    
    $.ajax({
        url: contr_link + '&action=getStores'
    })
    .done(function(data){
        $('#personalized_freq').html(data);
        $('#personalized_freq > tr:nth-child(1) > td > label > input').change(function(){
            var name = $(this).attr("name");
            if($(this)[0].checked){
                $('input[name="'+name+'"]').prop( "checked", true );
            }else{
                $('input[name="'+name+'"]').prop( "checked", false );
            }
        });
    })
    .fail(function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
    });

    $('#validate_weekFrequency_gall').click(function(){
        json =[];
        var rows = $('#personalized_freq > tr');
        rows.each(function(){
            var cronDays = '';
            var row_inputs = $(this).find("td > label > input");
            row_inputs.each(function(){
                //cronDays += ($(this)[0].checked == true)?$(this)[0].getAttribute('name'):'';
                cronDays += ($(this)[0].checked == true)?($(this)[0].value +'|'):'';
            });            
            item={};
            item['store'] = $(this).find('td >input[name="idx"]').val();
            item['cronDays'] = cronDays;
            item['email'] = $(this).find('td >input[name="email"]').val();
            json.push(item);
        });

        console.log(json);

        $.ajax({
            url: contr_link + '&action=setcronDays&cronDays=' + JSON.stringify(json)
        })
        .done(function(data){
            console.log('inserted');
            $.notify("saved !","success");
        })
        .fail(function(xhr, textStatus, errorThrown) {
            console.log(errorThrown)
        });
    });


    // sync
    $('#sync_orders').click(function(){
        $.ajax({
            url: contr_link + '&action=createOrders'
        })
        .done(function(data){
            console.log(data);
            console.log('** orders synced **');
            $.notify("orders synced !","success");
        })
        .fail(function(xhr, textStatus, errorThrown) {
            console.log(errorThrown)
        });
    });

});