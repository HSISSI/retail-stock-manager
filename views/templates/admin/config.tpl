<script>
  var conf_url = "{$conf_url}";
</script> 

<div class="panel" id="new_config_panel">
    <div class="panel-heading">
        {l s='Nouvelle configuration' mod='ami'}
    </div>
    <div class="panel-body">
        <div class="form-group">
			    <form>
			      <div class="col-lg-9">
				      <label for="cm_search">Choisir un code model: </label>
              <input list="cm_lst" id="selectedMC" name="selectedMC" placeholder="taper pour rechercher.." />
              <datalist class="fixed-width-xl" id="cm_lst">
              </datalist>
			      </div>
            <button type="submit" value="1" id="cm_form_submit_btn" name="cm_form_submit_btn" class="btn btn-default pull-right">
              <i class="process-icon-save"></i> Recupérer les articles
		        </button>
          </form>
        </div>

        <div class="form-group">
          <div class="table-responsive-row clearfix">
            <table class="table">
              <thead>
                <tr>
                  <th class="fixed-width-xs left">
						        <span class="title_box active">Code article</span>
					        </th>
                  <th class="fixed-width-xl left">
  						      <span class="title_box active">Libelé</span>
	  				      </th>
                  <th class="fixed-width-xs left">
			  			      <span class="title_box active">Cycle de vie</span>
				  	      </th>
                  <th class="fixed-width-xs left">
			  			      <span class="title_box active"><input type="text" placeholder="MODEL MINI" id="mc_mini" value=""/></span>
				  	      </th>
                  <th class="fixed-width-xs left">
			  			      <span class="title_box active"><input type="text" placeholder="MODEL MAXI" id="mc_maxi" value=""/></span>
				  	      </th>
                  <th class="fixed-width-xs left">
			  			      <span class="title_box"></span>
				  	      </th>
                </tr>
              </thead>
              <tbody id="lst_articles"></tbody>
            </table>
          </div>
        </div>

        <div class="form-group">
          <div id="listStores"></div>
        </div>
        
    </div>
        
    <div class="panel-footer">
      <button type="submit" value="1" id="submitOptionsconfiguration" name="submitOptionsconfiguration" class="btn btn-default pull-right">
        <i class="process-icon-save"></i> Enregistrer
		  </button>
	  </div>
</div>
<div class="panel" id="config_history_panel">
    <div class="panel-heading">
        {l s='Listes des configurations' mod='ami'}
    </div>
    <div class="panel-body">
      <div class="form-group">
          <div class="table-responsive-row clearfix">
                <button type="submit" value="1" id="add_new_config" name="add_new_config" class="btn btn-default pull-right">
                  <i class="process-icon-plus"></i> Ajouter
		            </button>
                <table class="table">
                    <thead>
                      <tr>
                        <th class="fixed-width-xs left">
						              <span class="title_box active">Codes model</span>
					              </th>
                        <th class="fixed-width-s left">
						              <span class="title_box active">Libelé</span>
					              </th>
                        <th class="fixed-width-xs left"></th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id="config_history_table">
                    </tbody>
                </table>
          </div>
        </div>
    </div>
</div>
<div class="panel" id="basic_config_panel">
    <div class="panel-heading">
        {l s='Configurations Principaux' mod='ami'}
    </div>
    <div class="panel-body">
      <div class="form-group">
          <div class="table-responsive-row clearfix">
                <table class="table">
                    <thead></thead>
                    <tbody id="basic_config_table">
                      <tr><td class="fixed-width-xs left">api key</td><td><input type="text" name="text"></td></tr>
                      <tr><td class="fixed-width-xs left">URL</td><td><input type="text" name="text"></td></tr>
                      <tr><td class="fixed-width-s left">Status de creation commande</td><td><select id="order_states_lst"></select></td></tr>
                      <tr><td class="fixed-width-xs left">Transporteur</td><td><select id="carriers_lst"></select></td></tr>
                    </tbody>
                </table>
          </div>
        </div>
    </div>
    <div class="panel-footer">
      <button type="submit" value="1" id="submitOptionsBasicConfiguration" name="submitOptionsBasicConfiguration" class="btn btn-default pull-right">
        <i class="process-icon-save"></i> Enregistrer
		  </button>
	  </div>
</div>


