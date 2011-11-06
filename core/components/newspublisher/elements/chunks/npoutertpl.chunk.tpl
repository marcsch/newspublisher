<div class="newspublisher">
        <h2>[[%np_main_header]]</h2>
  <form action="[[~[[*id]]]]" method="post">
        [[!+np.error_header:ifnotempty=`<h3>[[!+np.error_header]]</h3>`]]
        [[!+np.errors_presubmit:ifnotempty=`[[!+np.errors_presubmit]]`]]
        [[!+np.errors_submit:ifnotempty=`[[!+np.errors_submit]]`]]
        [[!+np.errors:ifnotempty=`[[!+np.errors]]`]]
            <input name="hidSubmit" type="hidden" id="hidSubmit" value="true" />
        [[+npx.insert]]
         <span class = "buttons">
             [[+npx.buttons]]
         </span>
        [[+np.post_stuff]]
  </form>
</div>