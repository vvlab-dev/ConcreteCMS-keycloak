<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Html\Service\Html $html
 * @var Concrete\Core\Application\Service\UserInterface $interface
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Core\Page\View\PageView $view
 * @var Concrete\Package\KeycloakAuth\Controller\SinglePage\Dashboard\System\Registration\Authentication\KeycloakMappings\Edit $controller
 * @var KeycloakAuth\Entity\Server $server
 * @var string $backTo
 * @var array $standardClaimsDictionary
 * @var array $fieldDictionary
 * @var array $attributeDictionary
 * @var string[] $usedFields
 */

?>
<div id="kc-mapping" v-cloak>

    <fieldset>
        <legend><?= t('Attribute maps') ?></legend>
        <table class="table table-hover table-condensed table-sm">
            <colgroup>
                <col />
                <col />
                <col width="1" />
            </colgroup>
            <thead>
                <tr>
                    <th><?= t('Claim ID') ?></th>
                    <th><?= t('User Attributes') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(a, index) in map.attributes">
                    <td>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm font-monospace kc-claimname" v-model.trim="a.claim" v-on:input="checkEmptyAttribute" v-bind:disabled="busy" spellcheck="false" />
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-sm btn-secondary btn-default" v-on:click.prevent="pickClaim(a.claim, (id) =&gt; a.claim = id)" v-bind:disabled="busy">...</button>
                            </span>
                        </div>
                        <div class="small text-muted" v-if="STANDARD_CLAIMS.hasOwnProperty(a.claim)">{{ STANDARD_CLAIMS[a.claim] }}</div>
                    </td>
                    <td>
                        <select class="form-control form-control-sm" v-model="a.attribute"  v-on:change="checkEmptyAttribute" v-bind:disabled="busy">
                            <option value="" v-if="a.attribute === ''"></option>
                            <option v-for="(value, key) in ATTRIBUTES" v-bind:value="key">{{ value }}</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" v-on:click.prevent="removeAttribute(index)" v-bind:disabled="busy">&times;</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>

    <fieldset>
        <legend><?= t('User creation and management') ?></legend>
        <table class="table table-hover table-condensed table-sm">
            <thead>
                <tr>
                    <th><?= t('Field') ?></th>
                    <th><?= t('Claim ID') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="field in displayedFields">
                    <td>{{ FIELDS[field] }}</td>
                    <td>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm font-monospace kc-claimname" v-model.trim="map.fields[field]" v-bind:disabled="busy" spellcheck="false" />
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-sm btn-secondary btn-default" v-on:click.prevent="pickClaim(map.fields[field], (id) =&gt; map.fields[field] = id)" v-bind:disabled="busy">...</button>
                            </span>
                        </div>
                    </td>
                    <td><div class="small text-muted">{{ STANDARD_CLAIMS[map.fields[field]] || '' }}</div></td>
                </tr>
            </tbody>
        </table>
    </fieldset>

    <fieldset>
        <legend><?= t('Received claims log') ?></legend>
        <div v-if="lastLoggedReceivedClaims === null">
            <?= t('The log of the last received claims is empty.') ?><br />
            <div v-if="logNextReceivedClaims">
                <?= t("You can try to log in using KeyCloak: we'll display the claims below.") ?><br />
                <button type="button" class="btn btn-sm btn-secondary btn-default" v-on:click.prevent="doLastLoggedReceivedClaimsOperation('refresh')" v-bind:disabled="busy"><?= t('Refresh') ?></button>
                <button type="button" class="btn btn-sm btn-secondary btn-default" v-on:click.prevent="doLastLoggedReceivedClaimsOperation('disable')" v-bind:disabled="busy"><?= t('Cancel') ?></button>
            </div>
            <div v-else>
                <?= t('Do you want to log the claims of the next login?') ?><br />
                <button type="button" class="btn btn-sm btn-secondary btn-default" v-on:click.prevent="doLastLoggedReceivedClaimsOperation('enable')" v-bind:disabled="busy"><?= t('Enable log') ?></button>
            </div>
        </div>
        <div v-else>
            <?= t("Here's the list of the last logged claims:") ?>
            <table class="table table-hover table-condensed table-sm">
                <thead>
                    <tr>
                        <th><?= t('Claim ID') ?></th>
                        <th><?= t('Claim value') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(value, name) in lastLoggedReceivedClaims">
                        <td>
                            <code>{{ name }}</code>
                            <div class="small" v-if="STANDARD_CLAIMS.hasOwnProperty(name)">{{ STANDARD_CLAIMS[name] }}</div>
                        </td>
                        <td><code style="white-space: pre-wrap">{{ JSON.stringify(value, null, '   ') }}</code></td>
                    </tr>
                </tbody>
            </table>
            <div class="float-end pull-right">
                <button type="button" class="btn btn-sm btn-secondary btn-default" v-on:click.prevent="doLastLoggedReceivedClaimsOperation('clear')" v-bind:disabled="busy"><?= t('Clear') ?></button>
            </div>
        </div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?= h($backTo) ?>" class="btn btn-secondary btn-default float-start float-left"><?= t('Cancel') ?></a>
            <button type="button" class="btn btn-primary float-end pull-right" v-on:click.prevent="save" v-bind:disabled="busy"><?= t('Save') ?></button>
        </div>
    </div>

    <div class="d-none hide" class="ccm-ui">
        <div id="kc-pick-claim" title="<?= t('Standard claims') ?>" class="ccm-ui">
            <div>
                <input type="text" class="form-control " id="claimFilter" v-model="claimFilter" ref="claimFilter" placeholder="<?= t('Search') ?>" spellcheck="false" />
            </div>
            <div v-if="filteredClaims.length === 0" class="alert alert-info">
                <?= t('No standard claims found') ?>
            </div>
            <div v-else style="height: 300px; overflow-y: auto">
                <table class="table table-hover table-condensed table-sm">
                    <tbody>
                        <tr v-for="filteredClaim in filteredClaims" v-bind:data-claim="filteredClaim.id" style="cursor: pointer">
                            <td><code>{{ filteredClaim.id }}</code></td>
                            <td>{{ filteredClaim.description }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

new Vue({
    el: '#kc-mapping',
    data() {
        const map = <?= json_encode($server->getClaimMap()) ?>;
        if (!map.fields) {
            map.fields = {};
        }
        if (!map.attributes) {
            map.attributes = [];
        }
        return {
            busy: false,
            STANDARD_CLAIMS: <?= json_encode($standardClaimsDictionary) ?>,
            FIELDS: <?= json_encode($fieldDictionary) ?>,
            ATTRIBUTES: <?= $attributeDictionary === [] ? '{}' : json_encode($attributeDictionary) ?>,
            USED_FIELDS: <?= json_encode($usedFields) ?>,
            claimFilter: '',
            map,
            logNextReceivedClaims: <?= json_encode($server->isLogNextReceivedClaims()) ?>,
            lastLoggedReceivedClaims: <?= json_encode($server->getLastLoggedReceivedClaims()) ?>,
        };
    },
    mounted() {
        this.checkEmptyAttribute();
    },
    computed: {
        displayedFields() {
            const result = [].concat(this.USED_FIELDS);
            Object.keys(this.map.fields).forEach((field) => {
                if (!result.includes(field)) {
                    result.push(field);
                }
            });
            return result;
        },
        filteredClaims() {
            const words = [];
            this.claimFilter.split(/\s+/).forEach((word) => {
                word = word.toLowerCase();
                if (words !== '' && !words.includes(word)) {
                    words.push(word);
                }
            });
            const result = [];
            Object.keys(this.STANDARD_CLAIMS).forEach((id) => {
                const description = this.STANDARD_CLAIMS[id];
                let satisfyFilter = words.length === 0;
                if (!satisfyFilter) {
                    const search = (id + ' ' + description).toLowerCase();
                    satisfyFilter = true;
                    words.some((word) => {
                        if (search.indexOf(word) < 0) {
                            satisfyFilter = false;
                            return true;
                        }
                    });
                }
                if (satisfyFilter) {
                    result.push({id, description});
                }
            });
            return result;
        },
    },
    methods: {
        checkEmptyAttribute() {
            if (!this.map.attributes.some((a) => a.claim === '' || a.attribute === '')) {
                this.map.attributes.push({claim: '', attribute: ''});
            }
        },
        removeAttribute(index) {
            this.map.attributes.splice(index, 1);
            this.checkEmptyAttribute();
        },
        pickClaim(preselectedFilter, cb) {
            if (typeof preselectedFilter === 'string') {
                this.claimFilter = preselectedFilter;
            }
            var $dlg = $('#kc-pick-claim');
            $dlg.dialog({
                modal: true,
                width: 500,
                open: () => {
                    this.$refs.claimFilter.focus();
                    this.$nextTick(() => this.$refs.claimFilter.select());
                },
                close: () => {
                    $dlg.off('click', 'tr[data-claim]');
                    $dlg.dialog('destroy');
                },
            });
            $dlg.on('click', 'tr[data-claim]', (e) => {
                const id = $(e.currentTarget).attr('data-claim');
                cb(id);
                $dlg.dialog('close');
                this.$nextTick(() => this.$forceUpdate());
            });
        },
        doLastLoggedReceivedClaimsOperation(operation) {
            if (this.busy) {
                return;
            }
            this.busy = true;
            $.ajax({
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate("kc-mappings-logop{$server->getID()}")) ?>,
                    operation,
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('claimsLogOperation', $server->getID())) ?>
            })
            .always(() => {
                this.busy = false;
            })
            .done((data) => {
                if (data) {
                    for (const k in data) {
                        if (this.hasOwnProperty(k)) {
                            this[k] = data[k];
                        }
                    }
                }
            })
            .fail((xhr, status, error) => {
                ConcreteAlert.dialog(ccmi18n.error, ConcreteAjaxRequest.renderErrorResponse(xhr, true));
            });
        },
        save() {
            if (this.busy) {
                return;
            }
            this.busy = true;
            $.ajax({
                data: {
                    <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>: <?= json_encode($token->generate("kc-mappings-save{$server->getID()}")) ?>,
                    map: JSON.stringify(this.map),
                },
                dataType: 'json',
                method: 'POST',
                url: <?= json_encode((string) $view->action('save', $server->getID())) ?>
            })
            .done((data) => {
                window.location.href = <?= json_encode($backTo) ?>;
            })
            .fail((xhr, status, error) => {
                this.busy = false;
                ConcreteAlert.dialog(ccmi18n.error, ConcreteAjaxRequest.renderErrorResponse(xhr, true));
            });
        },
    },
});

});
</script>
