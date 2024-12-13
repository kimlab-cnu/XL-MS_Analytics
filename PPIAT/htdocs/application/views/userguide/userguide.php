    <main class="contents">
      <section class="userguide">
       <h4>READ ME! - User Guide</h4>
       <div class="userguide" style= "overflow:auto;">
        <div class="section1">
          <div class="sub_section1">
            <div>
              <div>
                <h5>What is PPIAT ?</h5>
                <p>
                  PPIAT is an analysis tool that automatically calculates the theoretical mass information required to 
                  apply Cross-Linking Mass Spectrometry(XL-MS) to targeted mass spectrometry analysis.
                </p>
              </div>
              <div>
                <div>
                  <h5>Major Function of PPIAT</h5>
                  <strong>Function 1.</strong>
                  <p>
                    Seaching Protein-Protein Interactions(PPIs).
                  </p>
                  <strong>Function 2.</strong>
                  <p>
                    Theoretical cross-linking and mass information calculation for Protein Interactome to apply XL-MS research to targeted mass spectrometry analysis.
                  </p>
                </div>
                <div>
                  <h5>Optional Function</h5>
                  <strong>Result summarization from PPIAT</strong>
                  <br>
                  <strong>Function 1.</strong>
                  <p>
                    Summarize results using Probability of Protein-Protein Interactions(PPIs).
                    User can summarize result from PPIAT using probability of PPIs. 
                    The probability of ppis were diviede 4 groups based on the confidence level.
                    (Highest Confidence : > 0.9, High Confidence : > 0.7, Medium Confidence : > 0.4, Low Confidence : > 0.15) 
                  </p>
                  <strong>Function 2.</strong>
                  <p>
                    User can summarize the result from PPIAT using peptide lists generated from another analysis tool such as Prego.
                    User can input peptide list of the target protein and the protein that interacts with the target protein respectively, and summarize the results for only those peptides.
                    <br>
                    Multiple peptides can be entered, and each peptide is separated by a space. (e.g: "TYYVNHNNR ALTHSVLKK NNIFEESYR HSIKDVHAR")
                  </p>
                </div>
              </div>      
              <div>
                <div>
                  <h5>PPIAT WORKFLOW</h5>
                  <h5>PPIAT has the following WORKFLOW:</h5>
                  <strong>A. Part of input Search information</strong>
                  <p>Researchers must enter the following information to use PPIAT.</p>
                  <p>
                    Please refer to the <span style='font-weight: bold;'>"Tutorial ! &nbsp;Steps of using analysis tools PPIA"</span> for more details.
                  </p>
                  <strong>B. Part of searching Protein interactome:</strong>
                  <p>
                    We utilize datasets for Reviewed human protein and Protein-protein interaction from Uniprot, STRING each.
                    <br>
                    <br>
                    * STRING and UniProt will be updated as information is updated.
                    <br>
                      The Human proteins are not 1:1 matched with protein-protein interaction yet. We will update that kind of problem based on result of Uniprot.
                  </p>
                  <strong>C. Part of Mass calculation considering all cases of cross-linking (Precursor/fragmented Ion Level):</strong>
                  <p>
                    All theoretically possible cross-linking cases and their masses considering the characteristics of 
                    the cross-linker (binding site, cleavability), charge, and modification are calculated at the precursor/product ion level.
                  </p>
                </div>
                <div>
                  <h5>Tutorial ! &nbsp;&nbsp;Steps of using analysis tools PPIAT </h5>
                  <div>
                    <p>
                      <strong>A. Input information on search page</strong>
                    </p>
                  </div>
                  <div>
                    <p>
                      Input information is as follows. (A-1 to A-7)
                    </p>
                  </div>
                  <div>
                    <strong>A-1. Input Target protein(EntryNumber)</strong>
                    <p>
                      For protein interaction search, the user uses the EntryNumber (e.g. Q6FHJ7, Q6ZWK4, etc.) for the target protein as input.
                    </p>
                    <br>
                    <strong>A-2. Select Enzyme for digestion</strong>
                    <p>
                      Researchers select the enzyme they want to use in XL-MS research.
                      <br>
                      You can choose from two types of enzymes, Trypsin and Chymotrypsin, that are mainly used in research, 
                      and we plan to add more types of enzymes in the future.
                    </p>
                    <strong>A-3. Select cross-linker for XL-MS</strong>
                    <p>
                      Researchers select the cross-linker they want to use in XL-MS research.
                      <br>
                      We provide 83 commercially available cross-linkers for XL-MS research, 
                      and a list of each cross-linker can be found in the "List of Cross-Linker" section. 
                      We plan to update the types of cross-linkers in the future.
                    </p>
                    <strong>A-4. Input Peptide Length range</strong>
                    <p>
                      Enter the peptide sequence range that the researcher wishes to analyze.
                    </p>
                    <strong>A-5. Input Number of Interaction Ranking</strong>
                    <p>
                      This is the value of how many interacting proteins the researcher will identify with the target protein.
                    </p>
                    <strong>A-6. Select Charge</strong>
                    <p>
                      Select the charge value the researcher wishes to analyze.
                      <br>
                      Charge values are divided into Precursor/Fragmented Charge and can be selected multiple times. 
                      (e.g. Precursor Charge: 2,3 / Fragment Charge: 1,2)
                    </p>
                    <strong>A-7. Select Modification</strong>
                    <p>
                      Select the modification information the researcher wishes to consider during analysis.
                      <br>
                      We provide carbamidomethyl (C) and oxidation (M), which are basically modifications applied in the current analysis.
                      As with Enzyme and Cross-linker, we plan to update various modifications and the editing function so that users can directly enter modifications.
                    </p>
                  </div>
                  <div>
                    <strong>B. Click Search Button after nput information on search page</strong>
                    <br>
                    <br>
                    <strong>C. Check the result (on result page)</strong>
                    <br>
                    <br>
                    <strong>D. Summarize the result using optional function on result page</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>   