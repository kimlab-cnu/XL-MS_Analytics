    <main class="contents">
      <section class="search">
        <p>Search Protein Interaction</p>
        <form method="POST" class="searchbox" action="/search/result">
          <div>
            <div>
              <ul>
                <li>
                  <input type="hidden" name="page_now" value="<?php 
                    if (empty($page_now)) {
                      echo 1;
                    } else {
                      echo $page_now;
                    }
                  ?>">
                </li>
                <li>
                  <label for="Protein_id">
                    <p>Protein :</p>
                    <input type="text" name="human_protein_reviewed" id="Protein_id" placeholder="Please enter the entry only. Please check isoform of human protein what our DB have on top of menu." required />
                  </label>
                </li>
                <li>
                  <label for="Enzyme">
                    <p>Enzyem :</p>
                    <select name="enzyme" require>
                      <?php 
                      foreach ($enzyme as $row)
                      {
                        echo '<option value="'.$row->id.'">'.$row->name.'</option>';
                      }
                      ?>
                    </select>
                  </label>
                </li>
                <li>
                  <label for="Crosslinker_id">
                    <p>Cross-Linker :</p>
                    <select name="crosslinker" require>
                      <?php
                      foreach ($crosslinker as $row)
                      {
                        echo '<option value="'.$row->id.'">'.$row->name.'</option>';
                      } 
                      ?>  
                    </select>
                  </label>
                </li>
              </ul>
            </div>
            <div>
              <ul>
                <li>
                  <label for="Peptide_length_min">
                    <p>Peptide length Range (Min): </p>
                    <input type="text" name="peptide_length_min" placeholder="How many peptides would you like to see?" required />
                  </label>
                </li>     
                <li>
                  <label for="Peptide_length">
                    <p>Peptide length Range (Max): </p>
                    <input type="text" name="peptide_length_max" placeholder="How many peptides would you like to see?" required />
                  </label>
                </li>
                <li>
                  <label for="Rank">
                    <p>Number of Interaction Ranking: </p>
                    <input type="text" name="sorting" placeholder="How many interaction would you like to see?" required />
                  </label>
                </li>
                
                <!-- 임시 사용 -->
                <li>
                  <label for="probability">
                    <p>Probability</p>
                    0.<input type="text" name="probability" /> 
                  </label>
                </li>
                <!--  -->

                <li>
                  <label for="PeptideCharge">
                    <p>Peptide Charge</p>
                    <label for="peptide_charge_1">
                      1 <input id="peptide_charge_1" type="checkbox" name="peptidecharge[]" value="1">
                    </label>
                    <label for="peptide_charge_2">
                      2 <input id="peptide_charge_2" type="checkbox" name="peptidecharge[]" value="2">
                    </label>
                    <label for="peptide_charge_3">
                      3 <input id="peptide_charge_3" type="checkbox" name="peptidecharge[]" value="3">
                    </label>
                    <label for="peptide_charge_4">
                      4 <input id="peptide_charge_4" type="checkbox" name="peptidecharge[]" value="4">
                    </label>
                    <label for="peptide_charge_5">
                      5 <input id="peptide_charge_5" type="checkbox" name="peptidecharge[]" value="5">
                    </label>
                    <label for="peptide_charge_6">
                      6 <input id="peptide_charge_6" type="checkbox" name="peptidecharge[]" value="6">
                    </label>
                    <label for="peptide_charge_7">
                      7 <input id="peptide_charge_&" type="checkbox" name="peptidecharge[]" value="7">
                    </label>
                    <label for="peptide_charge_8">
                      8 <input id="peptide_charge_*" type="checkbox" name="peptidecharge[]" value="8">
                    </label>
                    <label for="peptide_charge_9">
                      9 <input id="peptide_charge_9" type="checkbox" name="peptidecharge[]" value="9">
                    </label>
                    <label for="peptide_charge_10">
                      10 <input id="peptide_charge_10" type="checkbox" name="peptidecharge[]" value="10">
                    </label>
                  </label>                
                </li>
                <li>
                  <label for="IonCharge">
                    <p>Ion Charge</p>
                    <label for="ion_charge_1">
                      1 <input id="ioncharge_1" type="checkbox" name="ioncharge[]" value="1">
                    </label>
                    <label for="ion_charge_2">
                      2 <input id="ioncharge_2" type="checkbox" name="ioncharge[]" value="2">
                    </label>
                    <label for="ion_charge_3">
                      3 <input id="ioncharge_3" type="checkbox" name="ioncharge[]" value="3">
                    </label>
                    <label for="ion_charge_4">
                      4 <input id="ioncharge_4" type="checkbox" name="ioncharge[]" value="4">
                    </label>
                    <label for="ion_charge_5">
                      5 <input id="ioncharge_5" type="checkbox" name="ioncharge[]" value="5">
                    </label>
                    <label for="ion_charge_6">
                      6 <input id="ioncharge_6" type="checkbox" name="ioncharge[]" value="6">
                    </label>
                    <label for="ion_charge_7">
                      7 <input id="ioncharge_7" type="checkbox" name="ioncharge[]" value="7">
                    </label>
                    <label for="ion_charge_8">
                      8 <input id="ioncharge_8" type="checkbox" name="ioncharge[]" value="8">
                    </label>
                    <label for="ion_charge_9">
                      9 <input id="ioncharge_9" type="checkbox" name="ioncharge[]" value="9">
                    </label>
                    <label for="ion_charge_10">
                      10 <input id="ioncharge_10" type="checkbox" name="ioncharge[]" value="10">
                    </label>

                  </label>
                </li>
              </ul>
            </div>
            <div class="modifications">
              <ul>
                <li>
                  <p>Modifications : </p>
                  <div>
                    <p>Carbamidomethyl (C)</p>
                    <label for="carbamidomethyl_c_static">
                      Static <input id="carbamidomethyl_c_static" type="checkbox" name="carbamidomethyl_c_static" value="Y">
                    </label>
                    <label for="carbamidomethyl_c_variable">
                      Variable <input id="carbamidomethyl_c_variavle" type="checkbox" name="carbamidomethyl_c_variable" value="Y">
                    </label>
                  </div>
                  <div>
                    <p>Oxidation (M)</p>
                    <lavel for="oxication_m_static">
                      Static <input id="oxidation_m_static" type="checkbox" name="oxidation_m_static" value="Y">
                    </lavel>
                    <label for="oxidation_m_variable">
                      Variable <input id="oxidation_m_variable" type="checkbox" name="oxidation_m_variable" value="Y">
                    </label>
                  </div>
                </li>
              </ul>                      
            </div>
          </div>
          <div>
            <button type="submit" onclick="location.href='result'">search</button>
          </div>
        </form>
      </section>
    </main>