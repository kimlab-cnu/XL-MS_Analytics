    <main class="contents">
      <section class="result">
        <div>
          <div>
            <p>The protein you are looking for<p>
            <h4><?php echo $search_protein ?></h4>
            <p>interacte with</p>
          </div>
          <table>
            <tr>
              <th> Name of Interacted Reviewed Human Pr]otein </th>
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
          <div id="mytable" style="width:100%; height:600px; overflow:auto;">
            <table>
                <tr>
                  <th>Protein1 Peptide</th>
                  <th>Cross Linker</th>
                  <th>Protrin2 Peptide</th>
                  <th>Protein1 C trem Mass</th>
                  <th>Center Mass</th>
                  <th>Protein2 N term Mass </th>
                  <th>Protein1 N term Mass</th>
                  <th>Center Mass</th>
                  <th>Protein2 C term Mass</th>
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
                  <td><?php echo $result[$i]['protein1_peptide']; ?></td>
                  <td><?php echo $crosslinker->name; ?>
                  </td>
                  <td><?php echo $result[$i]['protein2_peptide']; ?></td>
                  <td><?php echo $result[$i]['protein1_peptide_c_term_mass']; ?></td>  
                  <td><?php echo $crosslinker->mass_center; ?></td>
                  <td><?php echo $result[$i]['protein2_peptide_n_term_mass']; ?></td>
                  <td><?php echo $result[$i]['protein1_peptide_n_term_mass']; ?></td>
                  <td><?php echo $crosslinker->mass_center; ?></td>
                  <td><?php echo $result[$i]['protein2_peptide_c_term_mass']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion_type']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion']; ?></td>
                  <td><?php echo $result[$i]['protein2_ion_type']; ?></td>
                  <td><?php echo $result[$i]['protein2_ion']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion_c_term_mass']; ?></td>
                  <td><?php echo $crosslinker->mass_center; ?></td>
                  <td><?php echo $result[$i]['protein2_ion_n_term_mass']; ?></td>
                  <td><?php echo $result[$i]['protein1_ion_n_term_mass']; ?></td>
                  <td><?php echo $crosslinker->mass_center; ?></td>
                  <td><?php echo $result[$i]['protein2_ion_c_term_mass']; ?></td>
                </tr>
                <?php } ?>
              </table>
              <?php ; ?>
          </div>
        <div>
          <button id="csvDownloadButton" onclick="csvExport()" style="cursor: pointer;">CSV Export</button>
        </div>
      </section>
    </main>