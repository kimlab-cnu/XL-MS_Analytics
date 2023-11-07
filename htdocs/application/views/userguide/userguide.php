    <main class="contents">
      <section class="userguide">
       <h4>READ ME! - User Guide</h4>
       <div class="userguide" style= "overflow:auto;">
        <div class="section1">
          <div class="sub_section1">
            <div>
              <p>
                We utilize datasets for Reviewed human protein and Protein-protein interaction from Uniprot, STRING each.
                We make the section of Human protein called "List of Human Protein" in our web platform, 
                you can find out the information of protein like Entry Number, Entry Name, STRING Reference, Length of Sequence.
                And we also have datasets for enzyme and cross linker. 
                Although there are variety kinds of them, we apply a few kinds of enzyme and Cross Linker first (e.g. Trypsin, Chymotrypsin ...). 
                Because that two kinds of enzyme are usually used in Proteomics based study. And Cross Linkers are also limited that are used.            
                You can check the kinds of Cross Linker and their information on tab of "list of Cross Linker".
              </p>
              <p>
                * The Human proteins are not 1:1 matched with rotein-protein interaction yet.
                We will update that kind of problem based on result of Uniprot.
              </p>
            </div>
            <div>
              <h5><strong>Tutorial !</strong> &nbsp;&nbsp;Steps of using analysis tools PPIAT </h5>
              <div>
                <p><strong>Step 1.</strong> Input a protein what you want to check pyhysical interaction with. </p>
                <p style='color: rgb(255, 70, 70); font-weight: bold;'><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> UniProt EntryNumber as a protein what you want (e.g: P02649 (Protein name: APOE E4)) </p>
                <br>
                <p><strong>Step 2.</strong> Choose the Enzyme to digeste the Protein. </p>
                <br>
                <p><strong>Step 3.</strong> Choose the Cross-Linker to use. </p>
                <br>
                <p><strong>Step 4.</strong> Input the range of Pepteide Length. </p>
                <br>
                <p><strong>Step 5.</strong> Input the Ranking that how many Protein interaction with. </p>
                <br>
                <p><strong>Step 6.</strong> Choose Precursor/Fragment Charge.</p>
                <br>
                <p><strong>Step 7.</strong> Choose the Modification.</p>
              </div>
              <div>
                <h5 style='color: rgb(255, 70, 70); font-weight: bold;'>Optional Function</h5>
                <p><strong> Function 1.</strong> Summarize results using Probability of PPIs </p>
                <p>
                  User can summarize searching Protein-Protein Interaction result by probability of ppis. 
                  The probability of ppis were diviede 4 groups based on the confidence level.
                  <br>
                  (Highest Confidence : > 0.9, High Confidence : > 0.7, Medium Confidence : > 0.4, Low Confidence : > 0.15) 
                </p>
                <br>
                <p><strong> Function 2.</strong> Summarize results using List of Peptides</p>
                <p>
                  User can summarize calculated mass result by peptide lists.
                  User can input peptide list of the target protein and the protein that interacts with the target protein respectively, and summarize the results for only those peptides.
                  <br>
                  Peptides can be entered multiple times, and each peptide is separated by a space. (e.g: "TYYVNHNNR ALTHSVLKK NNIFEESYR HSIKDVHAR")
                  

                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="section2">
          <div class="sub_section2">
            <div>
              <p>
                * We make a result that whcich proteins are interacted with and there score using STRING database.
              </p>
              <p>
                Displayed result table has inpormation about peptide-sequences and their mass value, 
                Cross Linker what you choose, ion type, ion sequence and their mass and interacted mass (2types).
              </p>       
            </div>

            <div>
              <h5> Result Table Destribtion </h5>
              <table>
                <tr>
                  <th>Columns type </th>
                  <th>Meaning </th>
                </tr>
                <tr>
                  <td>Protein1 Peptide</td>
                  <td>Peptide sequence of the protein which interaction you want to know 
                      <br>
                      (e.g: peptides of input protein)    
                  </td>
                </tr>
                <tr>
                  <td>Cross Linker</td>
                  <td>Cross Linker used for protein interaction</td>
                </tr>
                <tr>
                  <td>Protrin2 Peptide</td>
                  <td>Peptide sequences of proteins that interact with specific protein.</td>
                </tr>
                <tr>                
                  <td>Protein1 C term Mass</td>
                  <td>Protein1 Peptide mass plus C term nass of Cross Linker.
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker.
                  </td>
                </tr>
                <tr>                
                  <td>Center Mass</td>
                  <td>if Cross Linker has cleavability, the mass of Cross Linker remaining after cleavage. </td>
                </tr>
                <tr>                
                  <td>Protein2 N term Mass </td>
                  <td>Protein2 Peptide mass plus N term mass of Cross Linker. 
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker.    
                  </td>
                </tr>
                <tr>                
                  <td>Protein1 N term Mass</td>
                  <td>Protein1 Peptide mass plus N term nass of Cross Linker.
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker.</td>
                </tr>
                <tr>                
                  <td>Protein2 C term Mass</td>
                  <td>Protein2 Peptide mass plus C term mass of Cross Linker. 
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker.    
                  </td>
                </tr>
                <tr>                
                  <td>Protein1 Ion type</td>
                  <td>Type of peptide fragment after Q2.</td>
                </tr>
                <tr>                
                  <td>Protein1 Ion</td>
                  <td>Fragmentation of protein1 peptide sequence</td>
                </tr>
                <tr>                
                  <td>Protein2 Ion type</td>
                  <td>Type of peptide fragment after Q2.</td>
                </tr>
                <tr>                
                  <td>Protein2 Ion</td>
                  <td>Fragmentation of protein2 peptide sequence</td>
                </tr>
                <tr>                
                  <td>Protein1 Ion C term Mass</td>
                  <td>Protein1 ion mass plus C term nass of Cross Linker.
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker. 
                  </td>
                </tr>
                <tr>                
                  <td>Protein2 Ion N term Mass</td>
                  <td>Protein2 ion mass plus N term nass of Cross Linker.
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker. </td>
                </tr>
                <tr>                
                  <td>Protein1 N term Mass</td>
                  <td>Protein1 ion mass plus N term nass of Cross Linker.
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker. </td>
                </tr>
                <tr>                
                  <td>Protein2 C term Mass</td>
                  <td>Protein2 ion mass plus C term nass of Cross Linker.
                      <br>
                      <br>
                      If Cross Linker has no cleavability, just added whole mass of Cross Linker. 
                  </td>
                </tr>
              </table>
            </div>
          </div>   
        </div>
      </section>
    </main>   