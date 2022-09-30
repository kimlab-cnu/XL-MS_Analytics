    <main class="contents">
      <section>
       <h4>READ ME! - User Guide</h4>
       <div class="userguide" style= "overflow:auto;">
        <div>
          <div>
            We utilize datasets for Reviewed human protein and Protein-protein interaction from Uniprot, STRING each.
            We make the section of Human protein called "List of Human Protein" in our web platform, 
            you can find out the information of protein like Entry Number, Entry Name, STRING Reference, Length of Sequence.
            And we also have datasets for enzyme and cross linker. 
            Although there are variety kinds of them, we apply a few kinds of enzyme and Cross Linker first (e.g. Trypsin, Chymotrypsin ...). 
            Because that two kinds of enzyme are usually used in Proteomics based study. And Cross Linkers are also limited that are used.            
            You can check the kinds of Cross Linker and their information on tab of "list of Cross Linker".
          </div>
          <div>
            The Human proteins are not 1:1 matched with rotein-protein interaction yet. 
            We will update that kind of problem based on result of Uniprot.
          </div>
          <div>
            <h4> How to Use </h4>
            <p><span>Step 1.</span> Input a protein what you want to check pyhysical interaction with. </p>
            <p><span>Step 2.</span> Choose the Enzyme to digeste the Protein. </p>
            <p><span>Step 3.</span> Choose the Cross-Linker to use. </p>
            <p><span>Step 4.</span> Input the range of Pepteide Length. </p>
            <p><span>Step 5.</span> Input the Ranking that how many Protein interaction with. </p>
            <p><span>Step 6.</span> Choose IonCharge.</p>
            <p><span>Step 7.</span> Choose the Modification.</p>
          </div>
        </div>
        <div>
          <div>
            We make a result that whcich proteins are interacted with and there score. 
            Displayed result table has inpormation about peptide-sequences and their mass value, 
            Cross Linker what you choose, ion type, ion sequence and their mass and interacted mass (2types). <br>          
          </div>
          <div>
            <p> Result Table Destribtion </p>
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
                <td>Protein1 C trem Mass</td>
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
      </section>
    </main>   