<?php global $session; ?>
            <div class="block">
                <div class="block-title hideable-block" style="background-color:#39aa1a"><?php echo t("Good time to use?"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                  <div style="background-color:#39aa1a; color:#fff">
                  
                    <div id="status-pre" style="padding:10px;"></div>
                    

                
                    <div class="trafficlight-body">
                    <div class="tl3"><div id="tl-red" class="trafficlight tl-red-off"></div></div>
                    <div class="tl3"><div id="tl-amber" class="trafficlight tl-amber-off"></div></div>
                    <div class="tl3"><div id="tl-green" class="trafficlight tl-green-off"></div></div>
                    </div>
                    <!--<img id="status-img" src="<?php echo $app_path; ?>images/new-tick.png"/>-->
                    <!--<div id="status-title" style="font-size:32px; font-weight:bold; height:32px"></div>-->
                    <!--<div id="status-until" style="height:16px; padding:10px;"></div>-->
                    <div id="gen-prc" style="height:16px; padding:10px;"></div><br>
                  </div>
                </div>
            </div>
            <?php /* if ($session['admin']) { ?>
            <div id="electricity_forecast" class="block">
                <div class="block-title hideable-block" style="background-color:#088400"><?php echo t("Forecast"); ?></div>

                <div class="block-content">
                  <div style="background-color:#fff; color:#000">
                                  
                    <div id="club_forecast_bound" style="width:100%; height:405px;">
                      <div id="club_forecast_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                </div>
            </div>
            <?php } */ ?>
            <div id="local_electricity_forecast" class="block">
                <div class="block-title hideable-block" style="background-color:#088400"><?php echo t("Local generator output"); ?>
                
                <div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div>
                <div class="visnav-block">
                  <!--<div class="visnav-club club-zoomin">+</div>-->
                  <!--<div class="visnav-club club-zoomout">-</div>-->
                  <div class="visnav-club club-left"><</div><div class="visnav-club club-right">></div>
                  <select class="period-select"></select>
                </div>
                
                
                </div>
                <div class="block-content">

                  <div style="background-color:#088400; color:#fff">
                    <div id="generation-status" style="font-size:32px; font-weight:bold"><?php echo t("---"); ?></div>
                    <?php echo t("Generating"); ?> <span id="generation-power">0</span> kW <?php echo t("now"); ?>
                  </div>
                  
                  <div class="no-padding">
                    <div class="triangle-wrapper">
                      <div class="triangle-down">
                        <div class="triangle-down-content triangle-forecast2-bg"></div>
                      </div>
                    </div>
                  </div>

                  <div style="padding:10px">
                    <div style="padding-top:5px; padding-bottom:5px">
                      <?php foreach ($concise_tariffs_table as $t) : ?>
                      <div class="legend-label-box" style="background-color:<?=$t->color?>"></div>
                      <span class="legend-label"><?=t(ucfirst($t->name))?></span>
                      <?php endforeach; ?>
                      <div class="legend-label-box" style="background-color:<?php echo $club_settings["generator_color"]; ?>"></div>
                      <span class="legend-label" ><?php echo t(ucfirst($club_settings["generator"])); ?></span>
                      <span id="club-price-legend">
                        <div class="legend-label-box" style="background-color:#fb1a80"></div>
                        <span class="legend-label" ><?php echo t("Best times to use power (0-10)");?></span>
                      </span>
                    </div>
                    
                    <div id="club_bargraph_bound" style="width:100%; height:405px;">
                      <div id="club_bargraph_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                  
                  <div style="background-color:#088400; color:#fff; padding:20px">
                  <div id="status-summary"><?php echo t(ucfirst($club_settings["generator"])." output is currently exceeding club consumption"); ?></div>
                  <!--<span style="font-size:14px; color:rgba(255,255,255,0.8)"><?php echo t("Light and dark grey portion indicates estimated ".$club_settings["generator"]." output and club consumption up to the present time"); ?></span>-->
                    
                  <!-- show/hide club price series on chart -->
                    <div id="showClubPrice" class="custom-control custom-checkbox d-flex justify-content-center pt-2" title="<?php echo t("Overlay the average club price offset by the available hydro") ?>">
                        <input type="checkbox" class="custom-control-input m-0 mr-2" id="showClubPriceInput">
                        <label class="custom-control-label m-0" for="showClubPriceInput"><strong><?php echo t("Best time to use power 10/10, worst time 0/10"); ?></strong></label>
                    </div>

                  </div>
                </div>
            </div>
            
            <div class="block">
                <div class="block-title hideable-block" style="background-color:#005b0b"><?php echo t("Your prices for power"); ?><div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content" style="padding: .6rem">
                    <table class="tariff table table-sm m-0">
                        <colgroup>
                            <col>
                            <col class="bg-info">
                            <col class="bg-danger">
                        </colgroup>
                        <thead>
                            <tr>
                            <th></th>
                            <th scope="col" style="background-color:<?=$club_settings["generator_color"]?>"><?=t(ucfirst($club_settings["generator"])); ?></th>
                            <th scope="col"><?php echo t("Extra electricity") ?></th>
                            </tr>
                        </thead>
                        <tbody id="tariffbody">
                        </tbody>
                    </table>
                    <div style="padding:10px; color:#888; font-size:14px">
                    <?=t('Unit prices include VAT');?>
                    <input type="checkbox" class="custom-control-input m-0 mr-2" id="showVAT" checked>
                  </div>
                </div>
            </div>
                              
            <?php if ($is_advisor ) { ?>
            <div class="block">
                <div class="block-title hideable-block" style="background-color:#005b0b">
                <?php echo t("Data Export"); ?>
                <div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content" style="padding: .6rem">
                  <form id="exportForm">
                    <label for="startDate">Start Date:</label>
                    <input type="text" id="startDate" name="startDate" placeholder="dd/mm/yyyy" required><br><br>
  
                    <label for="endDate">End Date (exclusive):</label>
                    <input type="text" id="endDate" name="endDate" placeholder="dd/mm/yyyy" required><br><br>
                    <button type="submit" id="exportMatchedPower">Export Matched Power</button>
                    <button type="submit" id="exportTotalDemand">Export Total Demand</button>
                  </form>
                  <script>
                    function parseDate(dateString) { //dd/mm/yyyy
                      var parts = dateString.split('/');
                      if (parts.length !== 3) {
                        console.error("Invalid date format");
                        return null;
                      }
                      var day = parseInt(parts[0], 10);
                      var month = parseInt(parts[1], 10) - 1; // Month is 0-based in JavaScript
                      var year = parseInt(parts[2], 10);

                      return new Date(year, month, day);
                    }
                    document.getElementById('exportForm').addEventListener('submit', function(event) {
                      event.preventDefault(); // Prevent form submission

                      // Fetch start and end dates from form
                      const startDate = parseDate(document.getElementById('startDate').value);
                      const endDate = parseDate(document.getElementById('endDate').value);

                      // Determine which button was clicked
                      var buttonClicked = event.submitter.id;

                      
                      // Perform action based on button clicked
                      if (buttonClicked === 'exportMatchedPower') {
                        exportConsumptionCSV(startDate, endDate, "matched");
                      } else if (buttonClicked === 'exportTotalDemand') {
                        exportConsumptionCSV(startDate, endDate, "demand");
                      }
                    });

                    function downloadCSV(filename, csvData) {
                      // Create a hidden anchor element
                      var hiddenElement = document.createElement('a');
                      hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvData);
                      hiddenElement.target = '_blank';
                      hiddenElement.download = filename;
                      document.body.appendChild(hiddenElement);
                        
                      // Trigger the download
                      hiddenElement.click();

                      // Clean up
                      document.body.removeChild(hiddenElement);
                    }

                    function formatDate(date) { // Format the date as "dd-mm-yyyy"

                      var day = date.getDate();
                      var month = date.getMonth() + 1; // January is 0, so we add 1
                      var year = date.getFullYear();

                      // Ensure leading zeros for day and month if needed
                      var formattedDay = (day < 10) ? '0' + day : day;
                      var formattedMonth = (month < 10) ? '0' + month : month;

                      return formattedDay + '-' + formattedMonth + '-' + year;
                    }

                    function exportConsumptionCSV(startDate, endDate, exportType) {
                      const startMillis = startDate.getTime();
                      const endMillis = endDate.getTime() - 1800; //exclusive end
                      
                      // Replace this with your export matched power logic
                      console.log('Exporting matched power data from ' + startMillis + ' to ' + endMillis);
                      $.ajax({
                        url: '<?php echo $club?>/export-csv',
                        type: 'GET',
                        data: {
                          start: startMillis,
                          end: endMillis,
                          export: exportType
                        },
                        dataType: 'text', // Expecting CSV as text data
                        success: function(data) {
                          const filename = "<?php echo $club; ?>_"+exportType+"_"+formatDate(startDate)+".csv";
                          downloadCSV(filename, data);
                        },
                        error: function(xhr, status, error) {
                          console.error('Error fetching CSV:', error);
                        }
                      });
                    }
                  </script>
                  </div>
                </div>
            <?php } ?>


