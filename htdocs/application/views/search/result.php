    <main class="contents">
      <section class="result">
        <div>
          <div>
            <p>The protein you are looking for<p>
            <h4><?php echo $search_protein ?></h4>
            <p>interacte with</p>
            <table>
              <tr>
                <th> Name of Interacted Reviewed Human Protein </th>
                <th> Interaction Probability </th>
              </tr>
              <?php for ($i=0;$i<count($interaction_info);$i++){ ?>
              <tr>
                <td>
                <?php echo $interaction_info[$i]['name']; ?>
                </td>
                <td>
                  <?php echo $interaction_info[$i]['score']/1000; ?>
                </td>
              </tr>
              <?php } ?>
            </table>
          </div>
          <div class="option">
            <form id="searchForm" method="POST" action="/search/result">
                <input type="hidden" name="human_protein_reviewed" value="<?php echo $input_info['human_protein']; ?>" />
                <input type="hidden" name="enzyme" value="<?php echo $input_info['enzyme']; ?>" />
                <input type="hidden" name="crosslinker" value="<?php echo $input_info['crosslinker']; ?>" />
                <input type="hidden" name="peptide_length_min" value="<?php echo $input_info['peptide_length_min']; ?>" />
                <input type="hidden" name="peptide_length_max" value="<?php echo $input_info['peptide_length_max']; ?>" />
                <input type="hidden" name="sorting" value="<?php echo $input_info['ranking']; ?>" />
                <input type="hidden" name="carbamidomethyl_c_static" value="<?php echo $input_info['carbamidomethyl_c_static']; ?>" />
                <input type="hidden" name="carbamidomethyl_c_variable" value="<?php echo $input_info['carbamidomethyl_c_variable']; ?>" />
                <input type="hidden" name="oxidation_m_static" value="<?php echo $input_info['oxidation_m_static']; ?>" />
                <input type="hidden" name="oxidation_m_variable" value="<?php echo $input_info['oxidation_m_variable']; ?>" />

                <?php
                  for ($i=0;$i<count($input_info['peptidecharge']);$i++) {
                    echo '<input type="hidden" name="peptidecharge[]" value="'.$input_info['peptidecharge'][$i].'" />';
                  }
                  for ($i=0;$i<count($input_info['ioncharge']);$i++) {
                    echo '<input type="hidden" name="ioncharge[]" value="'.$input_info['ioncharge'][$i].'" />';
                  }
                ?>

                <input id="pageNow" type="hidden" name="page_now" value="<?php echo $input_info['page_now']; ?>" />
                <h4>Setting</h4>
                <div>
                  <div class="setting">
                    <p>Min of Interaction Probability</p>
                    <input type="number" step="0.001" min=0 max=1 name="score_min" value="<?php if (!empty($input_info['score_min'])) {echo $input_info['score_min'];} ?>"/> 
                    <p>Max of Interaction Probability</p>
                    <input type="number" step="0.001" min=0 max=1 name="score_max" value="<?php if (!empty($input_info['score_max'])) {echo $input_info['score_max'];} ?>"/>
                  </div>
                  <div class="option_annotation">
                    <p>Highest Confidence of Interaction Probability : > 0.9</p>
                    <p>High Confidence of Interaction Probability : > 0.7</p>
                    <p>Medium Confidence of Interaction Probabilty : > 0.4</p>
                    <p>Low Confidence of Interaction Probability : > 0.15</p>
                  </div>
                </div>
                <button type="submit">UPDATE</button>
              </form>
          </div>
          <div id="mytable">
            <table>
                <tr>
                  <th>Index</th>
                  <th>Score</th>
                  <th>Protein1 Peptide</th>
                  <th>Cross Linker</th>
                  <th>Protrin2 Peptide</th>
                  <th>Peptide Charge</th>
                  <th>Protein1 C trem Mass</th>
                  <th>Center Mass</th>
                  <th>Protein2 N term Mass</th>
                  <th>Protein1 N term Mass</th>
                  <th>Center Mass</th>
                  <th>Protein2 C term Mass</th>
                  <th>Ion Charge</th>
                  <th>Protein1 Ion type</th>
                  <th>Protein1 Ion</th>
                  <th>Protein2 Ion type</th>
                  <th>Protein2 Ion</th>
                  <th>Protein1 Ion C term Mass</th>
                  <th>Center Mass</th>
                  <th>Protein2 Ion N term Mass</th>
                  <th>Protein1 N term Mass</th>
                  <th>Center Mass</th>
                  <th>Protein2 C term Mass</th>
                </tr> 
                <?php for ($i=0; $i<count($result); $i++) { ?>
                <tr>
                  <td><?php echo $result[$i]['id']; ?></td>
                  <td><?php echo $result[$i]['combined_score']; ?></td>
                  <td><?php echo $result[$i]['protein1_peptide']; ?></td>
                  <td><?php echo $crosslinker->name; ?></td>
                  <td><?php echo $result[$i]['protein2_peptide']; ?></td>
                  <td><?php echo $result[$i]['peptidecharge']; ?></td>
                  <td><?php echo $result[$i]['protein1_peptide_c_term_mass']; ?></td>  
                  <td><?php echo $result[$i]['center_mass_peptide_1']; ?></td>
                  <td><?php echo $result[$i]['protein2_peptide_n_term_mass']; ?></td>
                  <td><?php echo $result[$i]['protein1_peptide_n_term_mass']; ?></td>
                  <td><?php echo $result[$i]['center_mass_peptide_2']; ?></td>
                  <td><?php echo $result[$i]['protein2_peptide_c_term_mass']; ?></td>
                  <td><?php echo $result[$i]['ioncharge']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion_type']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion']; ?></td>
                  <td><?php echo $result[$i]['protein2_ion_type']; ?></td>
                  <td><?php echo $result[$i]['protein2_ion']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion_c_term_mass']; ?></td>
                  <td><?php echo $result[$i]['center_mass_ion_1']; ?></td>
                  <td><?php echo $result[$i]['protein2_ion_n_term_mass']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion_n_term_mass']; ?></td>
                  <td><?php echo $result[$i]['center_mass_ion_2']; ?></td>
                  <td><?php echo $result[$i]['protein2_ion_c_term_mass']; ?></td>
                </tr>
                <?php } ?>
              </table>
              <?php ; ?>
          </div>
          <div id="paginationHtml">
            <?php 
              if ($input_info['page_now'] == 1) {
                echo '<p class="pagination_checked" onclick="paginationBtn()">1</p>';
              } else {
                echo '<p onclick="paginationBtn()">1</p>';
              }
              
              for ($i=0;$i<count($input_info['pagination_count']);$i++) {
                if ($input_info['pagination_count'][$i] == $input_info['page_now']) {
                  echo '<p class="pagination_checked" onclick="paginationBtn()">'.$input_info['pagination_count'][$i].'</p>';
                } else {
                  echo '<p onclick="paginationBtn()">'.$input_info['pagination_count'][$i].'</p>';
                }
                
              }
              if ($input_info['page_now'] == $input_info['page_group_total']) {
                echo '<p class="pagination_checked" onclick="paginationBtn()">'.$input_info['page_group_total'].'</p>';
              } else {
                echo '<p onclick="paginationBtn()">'.$input_info['page_group_total'].'</p>';
              }
            ?>
          </div>
          
          <!-- pagination btn -->
          <script>
            function paginationBtn() {
              var page_now = event.target.textContent;
              document.getElementById('pageNow').value = page_now;
              document.getElementById('searchForm').submit();
            }
          </script>

          <!-- result csv -->
          <div>
            <form method="POST" action="/search/result_csv">
              <input type="hidden" name="human_protein_reviewed" value="<?php echo $input_info['human_protein']; ?>" />
              <input type="hidden" name="enzyme" value="<?php echo $input_info['enzyme']; ?>" />
              <input type="hidden" name="crosslinker" value="<?php echo $input_info['crosslinker']; ?>" />
              <input type="hidden" name="peptide_length_min" value="<?php echo $input_info['peptide_length_min']; ?>" />
              <input type="hidden" name="peptide_length_max" value="<?php echo $input_info['peptide_length_max']; ?>" />
              <input type="hidden" name="sorting" value="<?php echo $input_info['ranking']; ?>" />
              <input type="hidden" name="carbamidomethyl_c_static" value="<?php echo $input_info['carbamidomethyl_c_static']; ?>" />
              <input type="hidden" name="carbamidomethyl_c_variable" value="<?php echo $input_info['carbamidomethyl_c_variable']; ?>" />
              <input type="hidden" name="oxidation_m_static" value="<?php echo $input_info['oxidation_m_static']; ?>" />
              <input type="hidden" name="oxidation_m_variable" value="<?php echo $input_info['oxidation_m_variable']; ?>" />
              <input type="hidden" step="0.001" min=0 max=1 name="score_min" value="<?php if (!empty($input_info['score_min'])) {echo $input_info['score_min'];} ?>"/> 
              <input type="hidden" step="0.001" min=0 max=1 name="score_max" value="<?php if (!empty($input_info['score_max'])) {echo $input_info['score_max'];} ?>"/>
              
              <?php
                for ($i=0;$i<count($input_info['peptidecharge']);$i++) {
                  echo '<input type="hidden" name="peptidecharge[]" value="'.$input_info['peptidecharge'][$i].'" />';
                }
                for ($i=0;$i<count($input_info['ioncharge']);$i++) {
                  echo '<input type="hidden" name="ioncharge[]" value="'.$input_info['ioncharge'][$i].'" />';
                }
              ?>

              <input id="pageNow" type="hidden" name="page_now" value="<?php echo $input_info['page_now']; ?>" />
              <button type="submit">Export Result</button>
            </form>
          </div>
      </section>
    </main>