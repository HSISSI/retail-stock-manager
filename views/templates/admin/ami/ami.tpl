<script>
  var contr_link = "{$contr_link}";
</script>

<div class="panel">
    <div class="panel-heading">
        {l s='Paramètrage des fréquences' mod='ami'}
    
        </div class="create_orders">
            <button type="submit" value="1" id="sync_orders" name="sync_orders" class="btn btn-default pull-right" style="padding-bottom:10px !important">
                <i class="process-icon-sync"></i> Cliquer pour lancer la creation des commandes d'aujourd'hui.
		    </button>
        <div>

    </div>
    <div class="panel-body">
        <div class="panel-body"> 
            <hr />
             <div class="table-responsive-row clearfix">
                <table class="table">
                    <thead>
                    </thead>
                    <tbody id="personalized_freq"></tbody>
                </table>
            </div>
		</div>
        

        <div class="panel-footer">
            <button type="submit" value="1" id="validate_weekFrequency_gall" name="validate_weekFrequency_gall" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Enregistrer
		    </button>
	    </div>
    </div>
</div>