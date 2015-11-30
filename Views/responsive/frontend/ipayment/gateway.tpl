{extends file='parent:frontend/index/index.tpl'}

{block name='frontend_index_content_left'}{/block}

{* Breadcrumb *}
{block name='frontend_index_start' append}
    {$sBreadcrumb = [['name'=>"{s name=PaymentSubmitLabel}{/s}"]]}
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}{/block}

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

{* Main content *}
{block name="frontend_index_content"}

    {if $recurringPayments}
        <form action="{url action=recurring forceSecure}" method="POST">
            <div>
                {if $recurringError}
                    <div class="ipayment--distance-top">
                        <strong>{s name=PaymentErrorMessage}{/s}</strong>
                        {include file="frontend/_includes/messages.tpl" type="error" content=$recurringError.errorMessage|escape|nl2br}
                        <span class="is--hidden">{$recurringError.errorCode}</span>
                    </div>
                {/if}
                <div>
                    <h2>{s name=PaymentRecurring}{/s}</h2>
                    {foreach $recurringPayments as $payment}
                        <div>
                            <input id="recurring_{$payment.id}" type="radio" name="orderId" value="{$payment.orderId}" {if $payment@first}checked="checked"{/if}>
                            <label for="recurring_{$payment.id}">{$payment.description|escape}</label>
                        </div>
                    {/foreach}
                </div>
            </div>
            <div class="ipayment--distance-top">
                <input type="submit" value="{s name=PaymentSubmitLabel}{/s}" class="btn is--primary right">
            </div>
        </form>
    {/if}
    <form action="{$gatewayUrl}" method="POST">
        <div>
            {foreach $gatewayParams as $name => $value}
                {if $name != 'addr_name'}
                    <input type="hidden" name="{$name}" value="{$value|escape}">
                {/if}
            {/foreach}

            {if $gatewayError}
                <div class="ipayment--distance-top">
                    <strong>{s name=PaymentErrorMessage}{/s}</strong>
                    {include file="frontend/_includes/messages.tpl" type="error" content=$gatewayError.errorMessage|escape|nl2br}
                    <span class="is--hidden">{$gatewayError.errorCode}</span>
                </div>
            {/if}

            {if $gatewaySecureImage}
                <div id="secure_image" class="ipayment--distance-top ipayment--image">
                    <img src="{link file='frontend/_public/src/img/ipayment.jpg'}" title="3D-Secure" alt="3D-Secure"/>
                </div>
            {/if}

            <div>
                <h2>{s name=PaymentInput}{/s}</h2>

                <div class="ipayment--distance-top-amount is--strong">
                    <label for="trx_amount">{s name=PaymentAmountLabel}{/s}</label>
                    <span id="trx_amount">{$gatewayAmount|currency}</span>
                </div>
                <div class="ipayment--distance-top">
                    <label for="addr_name">{s name=PaymentAdressNameLabel}{/s}</label>
                    <input type="text" value="{$gatewayParams.addr_name|escape}" id="addr_name" name="addr_name">
                </div>
                <div class="ipayment--distance-top">
                    <label for="cc_number1">{s name=PaymentCreditCardNumber}{/s}</label>
                    <input type="hidden" value="" id="cc_number" name="cc_number">
                    <input type="text" class="cc_number" maxlength="4" id="cc_number1" name="cc_number1" onkeyup="checkKK(this, 'cc_number2');" autocomplete="off">
                    <input type="text" class="cc_number" maxlength="4" id="cc_number2" name="cc_number2" onkeyup="checkKK(this, 'cc_number3');" autocomplete="off">
                    <input type="text" class="cc_number" maxlength="4" id="cc_number3" name="cc_number3" onkeyup="checkKK(this, 'cc_number4');" autocomplete="off">
                    <input type="text" class="cc_number" maxlength="4" id="cc_number4" name="cc_number4" onkeyup="checkKK(this, '');" autocomplete="off">
                </div>
                <div class="ipayment--distance-top">
                    <label for="cc_checkcode">{s name=PaymentCheckCodeLabel}{/s}</label>
                    <input id="cc_checkcode" type="text" class="cc_number" value="" maxlength="4" size="4" name="cc_checkcode">
                    <span class="cc_checkcode_notice">
                        {s name=PaymentCheckCodeNotice}{/s}
                    </span>
                </div>

                <div class="ipayment--distance-top ipayment--select-month">
                    <label class="ipayment--label-select-month left" for="cc_expdate_month">{s name=PaymentExpDateLabel}{/s}</label>
                    <select id="cc_expdate_month" name="cc_expdate_month">
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
                    <select name="cc_expdate_year">
                        {for $i=date("Y"); $i<=date("Y")+10; $i++}
                            <option>{$i}</option>
                        {/for}
                    </select>
                </div>
            </div>
        </div>

        <div class="ipayment--distance-top">
            <a class="btn is--secondary" href="{url controller=account action=payment sTarget=checkout sChange=1}" title="{s name=AccountLinkChangePayment namespace=frontend/account/index}{/s}">
                {s name=AccountLinkChangePayment namespace=frontend/account/index}{/s}
            </a>
            <input type="submit" value="{s name=PaymentSubmitLabel}Zahlung abschließen{/s}" class="btn is--primary right">
        </div>
    </form>
{/block}
