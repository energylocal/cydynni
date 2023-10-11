<div class="block">
                <div class="block-title bg-tips"><?php echo t("Tips"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content bg-tips" style="padding:20px">
                    <figure class="tips-appliance show-fig">
                        <img src="<?php echo $app_path; ?>images/dishwasher.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("DISHWASHER") ?></h2>
                            </div>
                            <?php echo t("The time you run your dishwasher can be moved to avoid morning and evening peaks and take advantage of ".$club_settings["generator"]." power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am).")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="<?php echo $app_path; ?>images/lamp.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("LED LIGHTS") ?></h2>
                            </div>
                            <?php echo t("LED lights can cut your lighting costs by up to 90%. There’s more information on our website and in the info pack on installing them in your house.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="<?php echo $app_path; ?>images/stove.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("COOKING") ?></h2>
                            </div>
                            <?php echo t("Putting a lid on your pan when you're cooking traps the heat inside so you don’t need to have the hob on as high. A simple and effective way to use less electricity.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="<?php echo $app_path; ?>images/slowcooker.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("SLOW COOKING") ?></h2>
                            </div>
                            <?php echo t("Slow cookers are very energy efficient, make tasty dinners and help you avoid using electricity during the evening peak (4 - 8pm) when you might otherwise be using an electric oven.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="<?php echo $app_path; ?>images/washingmachine.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("WASHING MACHINE") ?></h2>
                            </div>
                            <?php echo t("The time you run your washing machine can be moved to avoid morning and evening peaks and take advantage of ".$club_settings["generator"]." power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am).")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="<?php echo $app_path; ?>images/fridge.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("FRIDGES & FREEZERS") ?></h2>
                            </div>
                            <?php echo t("Try to minimise how often and how long you need to open the doors. Wait for cooked food to cool before putting it in the fridge. Older fridges and freezers can be very inefficient and costly to run.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="<?php echo $app_path; ?>images/lightbulb.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("LIGHTS") ?></h2>
                            </div>
                            <?php echo t("Switching off lights and appliances when not in use is a simple and effective way to use less electricity. You can make a special effort to do this during the morning and evening peaks.")
                            ?>
                        <figcaption>
                    </figure>
                    
                    <div>
                        <div class="tips-arrow-outer-wrapper">
                            <div class="tips-arrow-inner-wrapper leftclick">
                                <div class="tips-leftarrow"></div>
                                <div class="tips-directions"><?php echo t("PREVIOUS"); ?></div>
                            </div>
                            <div class="tips-arrow-inner-wrapper rightclick">
                                <div class="tips-directions"><?php echo t("NEXT TIP"); ?></div>
                                <div class="tips-rightarrow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
