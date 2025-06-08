let pbCurrencies = [];


jQuery(document).ready(function ($) {

    function showStep(step) {
        $(".pb-tab-nav li, .pb-tab-content").removeClass("active");
        $(`.pb-tab-nav li[data-step="${step}"]`).addClass("active");
        $(`.pb-tab-content[data-step="${step}"]`).addClass("active");

        const percent = (step / 3) * 100;
        $(".pb-progress-bar").css("width", percent + "%");
		
		if (step === 2) {
			renderCurrencies($);
		}

    }

    $(".prev-step").click(function () {
        let current = parseInt($(".pb-tab-content.active").data("step"));
        showStep(current - 1);
    });

    $("#pb-step1-next").click(function () {
        const publicKey = $("#pb_public_key").val();
        const privateKey = $("#pb_private_key").val();

        $("#pb_step1_error").hide();
        $("#pb_step1_loader").show();
        $(this).prop("disabled", true);

        $.post(PremiumBlackOnboarding.ajax_url, {
            action: "pb_validate_keys",
            nonce: PremiumBlackOnboarding.nonce,
            public: publicKey,
            private: privateKey
        }).done(function (response) {
            if (response.success) {
				$("#pb-notification").fadeIn().delay(5000).fadeOut();
				pbCurrencies = response.data.currencies;
                showStep(2);
				// Nach erfolgreicher API-Key-Prüfung
				

            } else {
                $("#pb_step1_error").text(response.data.message).show();
            }
        }).fail(function () {
            $("#pb_step1_error").text("An unexpected error occurred.").show();
        }).always(function () {
            $("#pb_step1_loader").hide();
            $("#pb-step1-next").prop("disabled", false);
        });
    });

    $(".next-step").click(function () {
		const currentStep = parseInt($(".pb-tab-content.active").data("step"));

		if (currentStep === 2) {
			const selectedCurrencies = $("#pb_currency_checkboxes input[type='checkbox']:checked");
			if (selectedCurrencies.length === 0) {
				$("#pb_step2_error").show();
				return;
			} else {
				$("#pb_step2_error").hide();
			}
		}

		showStep(currentStep + 1);
	});

    $("#pb-finish").click(function () {

        $.post(PremiumBlackOnboarding.ajax_url, {
            action: "pb_save_onboarding_data",
            nonce: PremiumBlackOnboarding.nonce,
            public_key: $("#pb_public_key").val(),
            private_key: $("#pb_private_key").val(),
            currencies: $("#pb_currency_checkboxes input[type='checkbox']:checked").map(function () {
                return $(this).val();
            }).get(),
        }, function (response) {
            if (response.success) {
                showStep(3);
            }
        });
    });

});

function renderCurrencies($) {
    const container = $("#pb_currency_checkboxes");
    container.empty();

    if (!pbCurrencies || pbCurrencies.length === 0) {
        container.append("<p>No currencies available.</p>");
        return;
    }

    pbCurrencies.forEach((currency, index) => {
        const checkboxId = `pb_currency_${index}`;
        const checkbox = $(`
            <div class="pb-currency-item">
                <label for="${checkboxId}">
                    <input type="checkbox" id="${checkboxId}" name="pb_currencies[]" value="${currency.CodeChain}">
                    ${currency.Name} (${currency.CodeChain.toUpperCase()})
                </label>
            </div>
        `);
        container.append(checkbox);
    });
}

jQuery("#pb_select_all").on("click", function () {
    jQuery("#pb_currency_checkboxes input[type='checkbox']").prop("checked", true);
});

jQuery("#pb_select_none").on("click", function () {
    jQuery("#pb_currency_checkboxes input[type='checkbox']").prop("checked", false);
});