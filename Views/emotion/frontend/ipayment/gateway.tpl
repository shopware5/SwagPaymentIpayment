{extends file='frontend/index/index.tpl'}

{block name='frontend_index_content_left'}{/block}

{* Breadcrumb *}
{block name='frontend_index_start' append}
    {$sBreadcrumb = [['name'=>"{s name=PaymentSubmitLabel namespace=frontend/ipayment/gateway}{/s}"]]}
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}
    <hr class="clear"/>
{/block}

{block name="frontend_index_header_javascript" append}
    <script type="text/javascript">
        function checkKK(field, next) {
            field.value = field.value.replace(/\D/, "");
            if (field.value.length > 4) {
                field.value = field.value.substr(0, 4);
            }
            if (field.value.length == 4 && next) {
                document.getElementById(next).focus();
            }
            // Refresh hidden field
            document.getElementById('cc_number').value = document.getElementById('cc_number1').value
            + document.getElementById('cc_number2').value
            + document.getElementById('cc_number3').value
            + document.getElementById('cc_number4').value;
        }
    </script>
{/block}
{block name="frontend_index_header_javascript" append}
    <style type="text/css">
        input.cc_number {
            width: 64px
        }

        #trx_amount {
            padding: 5px;
            display: inline-block;
            margin: 0.5em 0;
        }

        .cc_checkcode_notice {
            display: block;
            margin-left: 264px;
        }

        #secure_image {
            position: absolute;
            right: 30px;
            top: 60px;
        }
    </style>
{/block}

{* Main content *}
{block name="frontend_index_content"}
    {if $recurringPayments}
        <form action="{url action=recurring forceSecure}" method="POST">
            <div class="grid_20 first register" style="margin:10px 0 10px 20px;width:960px;">
                {if $recurringError}
                    <div class="error">
                        <strong>{s name=PaymentErrorMessage namespace=frontend/ipayment/gateway}{/s}</strong><br/>
                        <span class="code hidden">{$recurringError.errorCode}</span>
                        {$recurringError.errorMessage|escape|nl2br}
                    </div>
                {/if}
                <div class="personal_settings" style="border-bottom-style: solid; border-bottom-width: 1px;">
                    <h2 class="headingbox_dark largesize">{s name=PaymentRecurring namespace=frontend/ipayment/gateway}{/s}</h2>
                    {foreach $recurringPayments as $payment}
                        <div>
                            <input id="recurring_{$payment.id}" style="margin: 10px 0 10px 55px;" type="radio"
                                   name="orderId" value="{$payment.orderId}" {if $payment@first}checked="checked"{/if}>
                            <label for="recurring_{$payment.id}" style="display: inline; float: none; cursor: pointer">
                                {$payment.description|escape}
                            </label>
                        </div>
                    {/foreach}
                </div>
            </div>
            <div class="actions" style="margin: 10px 0 10px 20px;display: inline-block;width:960px;">
                <input type="submit" value="{s name=PaymentSubmitLabel namespace=frontend/ipayment/gateway}{/s}" class="button-right large right">
            </div>
        </form>
    {/if}
    <form action="{$gatewayUrl}" method="POST">
        <div class="grid_20 first register" style="margin:10px 0 10px 20px;width:960px;">
            {foreach $gatewayParams as $name => $value}
                {if $name != 'addr_name'}
                    <input type="hidden" name="{$name}" value="{$value|escape}">
                {/if}
            {/foreach}
            {if $gatewayError}
                <div class="error"><strong>{s name=PaymentErrorMessage namespace=frontend/ipayment/gateway}{/s}</strong><br/>
                    <span class="code hidden">{$gatewayError.errorCode}</span>
                    {$gatewayError.errorMessage|escape|nl2br}
                </div>
            {/if}
            <div class="personal_settings" style="position: relative; border-bottom-style: solid; border-bottom-width: 1px;">
                <h2 class="headingbox_dark largesize">{s name=PaymentInput namespace=frontend/ipayment/gateway}{/s}</h2>

                <div>
                    <label for="trx_amount">{s name=PaymentAmountLabel namespace=frontend/ipayment/gateway}{/s}</label>
                    <span id="trx_amount">{$gatewayAmount|currency}</span>
                </div>
                <div>
                    <label for="addr_name">{s name=PaymentAdressNameLabel namespace=frontend/ipayment/gateway}{/s}</label>
                    <input class="text" type="text" value="{$gatewayParams.addr_name|escape}" id="addr_name" name="addr_name">
                </div>
                <div>
                    <label for="cc_number1">{s name=PaymentCreditCardNumber namespace=frontend/ipayment/gateway}{/s}</label>
                    <input type="hidden" value="" id="cc_number" name="cc_number">
                    <input class="text cc_number" maxlength="4" id="cc_number1" name="cc_number1" onkeyup="checkKK(this, 'cc_number2');" autocomplete="off">
                    <input class="text cc_number" maxlength="4" id="cc_number2" name="cc_number2" onkeyup="checkKK(this, 'cc_number3');" autocomplete="off">
                    <input class="text cc_number" maxlength="4" id="cc_number3" name="cc_number3" onkeyup="checkKK(this, 'cc_number4');" autocomplete="off">
                    <input class="text cc_number" maxlength="4" id="cc_number4" name="cc_number4" onkeyup="checkKK(this, '');" autocomplete="off">
                </div>
                <div>
                    <label for="cc_checkcode">{s name=PaymentCheckCodeLabe namespace=frontend/ipayment/gatewayl}{/s}</label>
                    <input id="cc_checkcode" class="text cc_number" type="text" value="" maxlength="4" size="4" name="cc_checkcode">
                    <span class="cc_checkcode_notice">
                        {s name=PaymentCheckCodeNotice namespace=frontend/ipayment/gateway namespace=frontend/ipayment/gateway}{/s}
                    </span>
                </div>

                <div>
                    <label for="cc_expdate_month">{s name=PaymentExpDateLabel namespace=frontend/ipayment/gateway}{/s}</label>
                    <select id="cc_expdate_month" name="cc_expdate_month" style="width:74px">
                        <option>01</option>
                        <option>02</option>
                        <option>03</option>
                        <option>04</option>
                        <option>05</option>
                        <option>06</option>
                        <option>07</option>
                        <option>08</option>
                        <option>09</option>
                        <option>10</option>
                        <option>11</option>
                        <option>12</option>
                    </select>
                    /
                    <select name="cc_expdate_year" style="width: 146px">
                        {for $i=date("Y");$i<=date("Y")+10;$i++}
                            <option>{$i}</option>
                        {/for}
                    </select>
                </div>
                {if $gatewaySecureImage}
                    <div id="secure_image">
                        <img src="{link file='frontend/_resources/images/ipayment.jpg'}" border="0" width="130" height="200" alt="3D-Secure"/>
                    </div>
                {/if}
                <div class="space">&nbsp;</div>
            </div>
        </div>

        <div class="actions" style="margin: 10px 0 10px 20px;display: inline-block;width:960px;">
            <a class="button-left large left" href="{url controller=account action=payment sTarget=checkout sChange=1}" title="{s name=AccountLinkChangePayment namespace=frontend/account/index}{/s}">
                {s name=AccountLinkChangePayment namespace=frontend/account/index}{/s}
            </a>
            <input type="submit" value="{s name=PaymentSubmitLabel namespace=frontend/ipayment/gateway}{/s}" class="button-right large right">
        </div>
    </form>
    <div class="doublespace">&nbsp;</div>
{/block}
