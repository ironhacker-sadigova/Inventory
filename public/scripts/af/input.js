/**
 * @author matthieu.napoli
 * @package AF
 */

/**
 * @namespace
 */
var AF = AF || {};

/**
 * Saisie d'un AF
 * @param {int} id ID de l'AF
 * @param {string} ref Ref de l'AF
 * @param {string} mode read/write/test
 * @param {int|null} idInputSet ID de l'inputSet, ou null si inputSet en session
 * @param {string} exitURL URL où envoyer l'utilisateur lors du clic sur le bouton "Exit"
 * @param {object} urlParams Paramètres d'URL additionnels à utiliser
 * @constructor
 */
AF.Input = function(id, ref, mode, idInputSet, exitURL, urlParams) {
	var that = this;

	/**
	 * id de l'AF
	 * @type {int}
	 */
	this.id = id;

	/**
	 * ref de l'AF
	 * @type {string}
	 */
	this.ref = ref;

	/**
	 * Formulaire (objet jquery)
	 */
	this.form = $("#" + ref);

	/**
	 * Mode de la saisie
	 * @type {string} read/write/test
	 */
	this.mode = mode;

	/**
	 * ID de l'inputSet, ou null si inputSet en session
	 * @type {int|null}
	 */
	this.idInputSet = idInputSet;

	/**
	 * Exit URL
	 * @type {string}
	 */
	this.exitURL = exitURL;

	/**
	 * Est-ce que la saisie a été modifiée
	 * @type {boolean}
	 */
	this.hasChanges = false;

	/**
	 * Sauvegarde l'URL par défaut de soumission du formulaire
	 * @type {string}
	 * @private
	 */
	this.defaultFormAction = this.form.prop("action");

	/**
	 * @type {function(data)}
	 * @private
	 */
	this.inputSavedHandler = this.onSaveHandler;

	/**
	 * Complétion de la saisie
	 * @type {AF.InputProgress}
	 */
	this.inputProgress = new AF.InputProgress(this.id, this.idInputSet, urlParams);

	/**
	 * Paramètres d'URL additionnels à utiliser
	 * @type {object}
	 */
	this.urlParams = urlParams;

	// Initialisation lorsque toute la page est chargée
	$(function() {
		// Se branche sur les boutons des sous-af
		that.form.find(".addSubAF").click(function() {
			var button = $(this);
			var idAFOwner = button.data("id-af-owner");
			var refComponent = button.data("ref-component");
			button.button("loading");
			that.addSubAF(idAFOwner, refComponent, function() {
				button.button("reset");
			});
		});
		// Se branche sur les boutons des sous-af
		that.form.find(".removeSubAF").click(function() {
			that.removeSubAF($(this).data("id-af-owner"), $(this).data("ref-component"), $(this).data("number"));
		});
		// Se branche sur les boutons du formulaire
		$(".inputSave").click(function() {
			that.save();
		});
		$(".inputReset").click(function() {
			that.reset();
		});
		$(".inputPreview").click(function() {
			that.previewResults();
		});
		$(".inputExit").click(function() {
			that.exit();
		});
		// Quand le formulaire est modifié
		that.onChange(function() {
			that.hasChanges = true;
			that.inputProgress.setStatus(AF.InputProgress.Status.IN_PROGRESS);
			that.form.find(".inputExit").addClass("btn-danger");
			that.form.find(".inputSave").prop("disabled", false);
			that.form.find(".inputReset").prop("disabled", false);
		});
	});

	// Handler appelé quand la saisie a été sauvegardée avec succès
	$.fn.inputSavedHandler = function(data, textStatus, jqXHR) {
		that.inputSavedHandler(data, textStatus, jqXHR);
	};

    // Handler pour l'historique d'une valeur
    $(".input-history").popover({
        placement: 'bottom',
        title: __('AF', 'inputInput', 'valueHistory'),
        html: true,
        content: '<img src="images/ui/ajax-loader.gif">'
    }).click(function(event) {
            var inputId = $(this).data('input-id');
            that.loadInputHistory(inputId, $(this));
        });
};

AF.Input.prototype = {

	/**
	 * Quitte le formulaire
	 */
	exit: function() {
		var that = this;
		if (this.hasChanges) {
			bootbox.confirm(
				__("AF", "inputInput", "confirmExitInput"),
				__("UI", "verb", "cancel"),
				__("UI", "verb", "confirm"),
				function(choice) {
					if (choice == true) {
						window.location.href = that.exitURL;
					}
				}
			);
		} else {
			window.location.href = that.exitURL;
		}
	},

	/**
	 * Réinitialise le formulaire
	 */
	reset: function() {
		if (this.hasChanges) {
			bootbox.confirm(
				__("AF", "inputInput", "confirmReinitializeInput"),
				__("UI", "verb", "cancel"),
				__("UI", "verb", "confirm"),
				function(choice) {
					if (choice == true) {
						location.reload();
					}
				}
			);
		} else {
			location.reload();
		}
	},

	/**
	 * Sauvegarde le formulaire
	 */
	save: function() {
		$(this).button("loading");
		this.form.submit();
	},

	/**
	 * Handler appelée pour la réponse de sauvegarde du formulaire
	 * @param {object} response JSON
	 * @param {string} textStatus
	 * @param {XMLHttpRequest} jqXHR
	 * @private
	 */
	onSaveHandler: function(response, textStatus, jqXHR) {
		var that = this;
		this.hasChanges = false;

		// Ajoute l'idInputSet aux l'URL
		if ("idInputSet" in response.data) {
			this.idInputSet = response.data.idInputSet;
			this.inputProgress.idInputSet = this.idInputSet;
			// URL de soumission du formulaire
			var currentUrl = this.form.attr("action");
			if ((currentUrl.indexOf("/idInputSet/") === -1) && (currentUrl.indexOf("?idInputSet=") === -1)) {
				this.form.attr("action", currentUrl + "?idInputSet=" + this.idInputSet);
			}
			// URL d'aperçu des résultats
			currentUrl = $("#tabs_tabResult").attr("data-remote");
			if ((currentUrl.indexOf("/idInputSet/") === -1) && (currentUrl.indexOf("?idInputSet=") === -1)) {
				$("#tabs_tabResult").attr("data-remote", currentUrl + "?idInputSet=" + this.idInputSet);
			}
			// URL du détails des calculs
			currentUrl = $("#tabs_tabCalculationDetails").attr("data-remote");
			if ((currentUrl.indexOf("/idInputSet/") === -1) && (currentUrl.indexOf("?idInputSet=") === -1)) {
				$("#tabs_tabCalculationDetails").attr("data-remote", currentUrl + "?idInputSet=" + this.idInputSet);
			}
		}

		// Réinitialise l'aspect des boutons
		this.form.find(".inputExit").removeClass("btn-danger");
		this.form.find(".inputSave").button("reset");
		// @see https://github.com/twitter/bootstrap/issues/6242
		setTimeout(function() {
			that.form.find(".inputSave").prop("disabled", true);
		}, 0);
		this.form.find(".inputReset").prop("disabled", true);

		// Cache l'aperçu des résultats
		$(".resultsPreview").hide();

		// Met à jour la complétion de la saisie
		var status = AF.InputProgress.getStatusByRef(response.data.status);
		this.inputProgress.setStatus(status);
		this.inputProgress.setCompletion(response.data.completion);

		// Affiche les messages d'erreur
		if ("errorMessages" in response) {
			this.form.parseFormErrors(jqXHR);
		}
		addMessage(response.message, response.type);
	},

	/**
	 * Affiche l'aperçu des résultats
	 */
	previewResults: function() {
		this.form.find(".inputPreview").button("loading");
		// Définit une nouvelle URL pour le submit
		var url = "af/input/results-preview/id/" + this.id;
		for (var key in this.urlParams) {
			url += '/' + key + '/' + this.urlParams[key];
		}
		this.form.prop("action", url);
		// Définit le handler de retour à utiliser
		this.inputSavedHandler = this.onResultsPreviewHandler;
		// Soumet le formulaire
		this.form.submit();
	},

	/**
	 * Handler appelée pour la réponse d'affichage d'aperçu des résultats
	 * @param {object} response JSON
	 * @param {string} textStatus
	 * @param {XMLHttpRequest} jqXHR
	 * @private
	 */
	onResultsPreviewHandler: function(response, textStatus, jqXHR) {
		this.form.find(".inputPreview").button("reset");
		$(".resultsPreviewContent").html(response.data);
		$(".resultsPreview").show();
		// Restaure l'URL de submit par défaut
		this.form.prop("action", this.defaultFormAction);
		// Restaure le handler par défaut
		this.inputSavedHandler = this.onSaveHandler;
		// Affiche les messages d'erreur
		if ("errorMessages" in response) {
			this.form.parseFormErrors(jqXHR);
		}
	},

	/**
	 * Ajoute un nouveau sous-AF
	 * @param {int} idAFOwner ID de l'AF qui possède ce component (pas forcément = AF affiché dans le cas
	 * de sous-formulaire dans des sous-formulaires)
	 * @param {string} refComponent
	 * @param {Function} onCompletion Callback appelée quand le sous-formulaire est ajouté
	 */
	addSubAF: function(idAFOwner, refComponent, onCompletion) {
		var that = this;

		// Numéro du sous-formulaire
		var numbers = that.form.find(".subAFGroup").filter(function(index, element) {
			return ($(element).data("id-af-owner") == idAFOwner)
				&& ($(element).data("ref-component") == refComponent);
		}).map(function(index, element) {
			return $(element).data("number");
		});
		var number = Math.max.apply(null, numbers) + 1;
		if (number < 0) {
			number = 0;
		}

		var url = "af/input/get-sub-af/id/" + idAFOwner + "/refComponent/" + refComponent + "/number/" + number;
		for (var key in this.urlParams) {
			url += '/' + key + '/' + this.urlParams[key];
		}

		$.get(url,
			function(data) {
				var domElement = $(data);
				that.form.find(".addSubAFGroup").filter(function(index, element) {
					return ($(element).data("id-af-owner") == idAFOwner)
						&& ($(element).data("ref-component") == refComponent);
				})
					.before(domElement);
				domElement.find(".removeSubAF").click(function() {
					that.removeSubAF($(this).data("id-af-owner"), $(this).data("ref-component"), $(this).data("number"));
				});
				onCompletion();
			}
		);
	},

	/**
	 * Supprime un sous-AF
	 * @param {int} idAFOwner ID de l'AF qui possède ce component (pas forcément = AF affiché dans le cas
	 * de sous-formulaire dans des sous-formulaires)
	 * @param {string} refComponent
	 * @param {int} number
	 */
	removeSubAF: function(idAFOwner, refComponent, number) {
		this.form.find(".subAFGroup").filter(function(index, element) {
			return ($(element).data("id-af-owner") == idAFOwner)
				&& ($(element).data("ref-component") == refComponent)
				&& ($(element).data("number") == number);
		}).remove();
	},

    /**
     * Ajoute un handler à l'évènement "change" de la saisie
     * Ne supprime pas les handlers précédents
     * @param {Function} handler Callback
     */
    onChange: function(handler) {
        // Pour tous les input du formulaire (utilise "on()" pour des raisons de performances)
        this.form.on("change keyup", ":input", handler);
        this.form.on("click", ".addSubAF", handler);
        this.form.on("click", ".removeSubAF", handler);
    },

    /**
     * Charge l'historique des valeurs d'une saisie
     * @param inputId {int}
     * @param button
     */
    loadInputHistory: function(inputId, button) {
        $.get("af/input/input-history/id/" + this.id + "/idInputSet/" + this.idInputSet + "/idInput/" + inputId,
            function (html) {
                button.data('popover').options.content = html;
                button.popover('show');
            }
        );
    }

};
