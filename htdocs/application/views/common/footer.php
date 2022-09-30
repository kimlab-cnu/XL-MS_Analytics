    <footer class = "footerlink">
      <div>
        <ul>
          <li>
            <a href="#">ⓒ 2022. kimlab. all rights reserved.</a>
            <br>
            <br>
            <a href="mailto:"test@gmail.com>Errors & Inquiries</a>
          </li>
        </ul>
      </div>
    </footer>
    <script type="text/javascript">
      class ToCSV {
        constructor() {
            document.querySelector('#csvDownloadButton').addEventListener('click', e => {
                e.preventDefault()
                this.getCSV('result.csv')
            })
        }

        downloadCSV(csv, filename) {
            let csvFile;
            let downloadLink;

            const BOM = "\uFEFF";
            csv = BOM + csv


            csvFile = new Blob([csv], {type: "text/csv"})

            downloadLink = document.createElement("a")

            downloadLink.download = filename;

            downloadLink.href = window.URL.createObjectURL(csvFile)

            downloadLink.style.display = "none"

            document.body.appendChild(downloadLink)

            downloadLink.click()
        }

        getCSV(filename) {
            const csv = []

            const rows = document.querySelectorAll("#mytable table tr")

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll("td, th")

                for (let j = 0; j < cols.length; j++)
                    row.push(cols[j].innerText)

                csv.push(row.join(","))
            }

            this.downloadCSV(csv.join("\n"), filename)
        }
    }

    document.addEventListener('DOMContentLoaded', e => {
        new ToCSV()
    })
    </script>
  </body>
</html>