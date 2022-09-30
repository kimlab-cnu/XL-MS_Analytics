    <main class="contents">
      <section class="search">
        <p>Search Protein Interaction<p>
        <form method="POST" class="searchbox" action="/search/result">
          <div>
            <div>
              <ul>
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
                <li>
                  <label for="PeptideCharge">
                    <p>Peptide Charge</p>
                    <select name="peptidecharge" required>
                      <option value="1">1</option>
                      <option value="2">2</option>
                      <option value="3">3</option>
                      <option value="4">4</option>
                      <option value="5">5</option>
                      <option value="6">6</option>
                      <option value="7">7</option>
                      <option value="8">8</option>
                      <option value="9">9</option>
                      <option value="10">10</option>
                    </select>
                  </label>                
                </li>
                <li>
                  <label for="IonCharge">
                    <p>Ion Charge</p>
                    <select name="ioncharge" required>
                      <option value="1">1</option>
                      <option value="2">2</option>
                      <option value="3">3</option>
                      <option value="4">4</option>
                      <option value="5">5</option>
                      <option value="6">6</option>
                      <option value="7">7</option>
                      <option value="8">8</option>
                      <option value="9">9</option>
                      <option value="10">10</option>
                    </select>
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