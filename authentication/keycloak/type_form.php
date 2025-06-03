<?php

defined('C5_EXECUTE') or die('Access denied.');

/**
 * @var Concrete\Core\Authentication\AuthenticationType $this
 * @var Concrete\Core\Form\Service\Widget\GroupSelector $groupSelector
 * @var Concrete\Core\Form\Service\Form $form
 * @var string $callbackUrl
 * @var bool $enableAttach
 * @var bool $enableDetach
 * @var bool $logoutOnLogoutEnabled
 * @var vvLab\KeycloakAuth\UI $ui
 * @var Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface $urlResolver
 * @var string $mappingsUrl
 * @var bool $editServers
 *
 * If $editServers is true:
 * @var vvLab\KeycloakAuth\Entity\Server[] $servers
 */

$monospaceAttr = ($ui->majorVersion >= 9 ? ['class' => 'font-monospace'] : ['style' => 'font-family: monospace;']) + [
    'autocomplete' => 'off',
    'spellcheck' => 'false',
];
?>
<div id="keycloakauth-config" v-cloak>
    <div v-if="visible">

        <div class="alert alert-warning">
            <strong><?= t('IMPORTANT') ?></strong>
            <?= t("Please remark that you'll need to update the Keycloak server configuration if you change the URL of this website, or if you change the Concrete configuration about pretty URLs.") ?>
        </div>

        <div v-if="servers !== null">
            <div class="alert alert-info">
                <?= t('Set the "Redirect URI" to: %s', '<code>' . h($callbackUrl) . '</code>') ?>
            </div>

            <div class="alert alert-info">
                <?= t('You can customize the attribute mappings <a href="%s">in this page</a>.', h($mappingsUrl)) ?>
            </div>
        </div>

        <div class="<?= $ui->formGroup ?>">
            <?= $form->label('displayName', t('Authentication Type Display Name')) ?>
            <?= $form->text('displayName', $this->getAuthenticationTypeDisplayName()) ?>
        </div>

        <div class="<?= $ui->formGroup ?>">
            <div class="checkbox">
                <label for="enableAttach">
                    <?= $form->checkbox('enableAttach', '1', $enableAttach) ?>
                    <span><?= t('Enable attaching existing local users to remote accounts') ?></span>
                </label>
            </div>
            <div class="checkbox">
                <label for="enableDetach">
                    <?= $form->checkbox('enableDetach', '1', $enableDetach) ?>
                    <span><?= t('Enable detaching local users from remote accounts') ?></span>
                </label>
            </div>
            <div class="checkbox" v-if="servers !== null">
                <label for="_multiServer" v-bind:class="{'text-muted': !canDisableMultiServer}">
                    <?= $form->checkbox('_multiServer', '1', false, ['v-model' => 'multiServerChecked', 'v-bind:disabled' => '!canDisableMultiServer']) ?>
                    <span><?= t('Enable support for multiple Keycloak servers') ?></span>
                </label>
            </div>
        </div>

        <div v-if="servers === null" class="alert alert-info">
            <?= t('Servers are handled in another way') ?>
        </div>
        <table v-else class="table table-bordered table-hover">
            <tbody>
                <tr v-for="(server, index) in servers"><td>
                    <input type="hidden" v-bind:name="`serverID_${index}`" v-bind:value="server.id || ''" />
                    <div class="<?= $ui->formGroup ?>">
                        <?= $form->label('', t('Realm root URL'), ['v-bind:for' => '`realmRootUrl_${index}`']) ?>
                        <?= $form->url('', '', [
                            'v-bind:id' => '`realmRootUrl_${index}`',
                            'v-bind:name' => '`realmRootUrl_${index}`',
                            'placeholder' => h('https://www.domain.com/realms/<realm>'),
                            'required' => 'required',
                            'spellcheck' => 'false',
                            'v-model.trim' => 'server.realmRootUrl',
                        ]) ?>
                    </div>
                    <div class="<?= $ui->formGroup ?> row">
                        <div class="col col-md-6">
                            <?= $form->label('', t('Client ID'), ['v-bind:for' => '`clientID_${index}`']) ?>
                            <?= $form->text('', '', [
                                'v-bind:id' => '`clientID_${index}`',
                                'v-bind:name' => '`clientID_${index}`',
                                'v-model.trim' => 'server.clientID',
                            ] + $monospaceAttr) ?>
                        </div>
                        <div class="col col-md-6">
                            <?= $form->label('', t('Client Secret'), ['v-bind:for' => '`clientSecret_${index}`']) ?>
                            <div class="input-group">
                                <?= $form->text('', '', [
                                    'v-bind:type' => "server._showPassword ? 'text' : 'password'",
                                    'v-bind:id' => '`clientSecret_${index}`',
                                    'v-bind:name' => '`clientSecret_${index}`',
                                    'v-model' => 'server.clientSecret',
                                ] + $monospaceAttr) ?>
                                <?php
                                if ($ui->majorVersion < 9) {
                                    ?>
                                    <span class="input-group-btn">
                                    <?php
                                }
                                ?>
                                <button
                                    type="button"
                                    class="btn <?= $ui->defaultButton ?>"
                                    v-bind:title="<?= h('server._showPassword ? ' . json_encode(t('Show client secret')) . ' : ' . json_encode(t('Hide client secret'))) ?>"
                                    v-on:click.prevent="server._showPassword = !server._showPassword"
                                >
                                    <i v-bind:class="<?= h('server._showPassword ? ' . json_encode($ui->faEyeSlash) . ' : ' . json_encode($ui->faEye)) ?>"></i>
                                </button>
                                <?php
                                if ($ui->majorVersion < 9) {
                                    ?>
                                    </span>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="<?= $ui->formGroup ?> row">
                        <div class="col col-md-6">
                            <?= $form->label('', t('Registration')) ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    v-bind:id="`registrationEnabled_${index}`"
                                    v-bind:name="`registrationEnabled_${index}`"
                                    v-model="server.registrationEnabled"
                                    value="1"
                                />
                                <label class="form-check-label" v-bind:for="`registrationEnabled_${index}`">
                                    <?= t('Allow automatic registration') ?>
                                </label>
                            </div>
                        </div>
                        <div class="col col-md-6" v-show="server.registrationEnabled">
                            <label class="form-label">
                                <?= t('Group to enter on registration') ?>
                            </label>
                            <?php
                            if ($ui->majorVersion >= 9) {
                                ?>
                                <concrete-group-input
                                    v-bind:group-id="server.registrationGroupID"
                                    choose-text="<?= tc('Group', 'None') ?>"
                                    v-bind:input-name="`registrationGroupID_${index}`"
                                ></concrete-group-input>
                                <?php
                            } else {
                                ob_start();
                                echo $groupSelector->selectGroup('xxxxxxxx');
                                $tmp = ob_get_contents();
                                ob_end_clean();
                                echo preg_replace(
                                    '/\sid=["\']xxxxxxxx["\']/i',
                                    '',
                                    preg_replace(
                                        '/\sname=["\']xxxxxxxx["\']/i',
                                        ' v-bind:name="`registrationGroupID_${index}`"',
                                        $tmp
                                    )
                                );
                            }
                            ?>
                        </div>
                    </div>
                    <div class="<?= $ui->formGroup ?> row">
                        <div class="col col-md-12">
                            <?= $form->label('', t('Options')) ?>
                            <div class="form-check">
                                <?php
                                if ($logoutOnLogoutEnabled) {
                                    ?>
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-bind:id="`logoutOnLogout_${index}`"
                                        v-bind:name="`logoutOnLogout_${index}`"
                                        v-model="server.logoutOnLogout"
                                        value="1"
                                    />
                                    <?php
                                } else {
                                    ?>
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        v-bind:id="`logoutOnLogout_${index}`"
                                        disabled="disabled"
                                    />
                                    <?php
                                }
                                ?>
                                <label class="form-check-label" v-bind:for="`logoutOnLogout_${index}`">
                                    <?= t('Logout from authentication server when logging out from this website') ?>
                                </label>
                                <?php
                                if ($logoutOnLogoutEnabled) {
                                    ?>
                                    <div class="small alert alert-info" v-if="server.logoutOnLogout">
                                        <?= t('You may want to set the valid post logout redirect URL to: %s', '<br /><code>' . h((string) $urlResolver->resolve(['/'])) . '</code>') ?>
                                    </div>
                                    <?php
                                } else {
                                    ?>
                                    <div class="small alert alert-info">
                                        <?= t('This feature requires Concrete CMS version %s or later', '9.2.1') ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="<?= $ui->formGroup ?>" v-if="multiServer">
                        <?= $form->label('', t('Use this server for email addresses matching any of these regular expressions'), ['v-bind:for' => '`emailRegexes_${index}`']) ?>
                        <textarea
                            class="form-control"
                            v-bind:id="`emailRegexes_${index}`"
                            v-bind:name="`emailRegexes_${index}`"
                            placeholder="<?= h(t('One regular expression per line') . "\n" . t('No regular expressions: apply to any users')) ?>"
                            v-model="server.emailRegexes"
                            rows="5"
                        ></textarea>
                    </div>
                    <div class="<?= $ui->textEnd ?>" v-if="servers.length &gt; 1">
                        <button
                            type="button"
                            class="btn btn-sm <?= $ui->defaultButton ?>"
                            v-bind:disabled="index &lt; 1"
                            v-on:click.prevent="moveServer(server, -1)"
                        >
                            <?= t('Move Up') ?>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm <?= $ui->defaultButton ?>"
                            v-bind:disabled="index &gt;= (servers.length - 1)"
                            v-on:click.prevent="moveServer(server, 1)"
                        >
                            <?= t('Move Down') ?>
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-danger"
                            v-on:click.prevent="removeServer(server)"
                        >
                            <?= t('Delete') ?>
                        </button>
                    </div>
                </td></tr>
            </tbody>
        </table>
        <div class="<?= $ui->textEnd ?>" v-if="multiServer">
            <button type="button" class="btn <?= $ui->defaultButton ?>" v-on:click.prevent="newServer">
                <?= t('Add Server') ?>
            </button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

function initialize(Vue, config)
{
    new Vue({
        components: config.components,
        el: '#keycloakauth-config',
        data() {
            <?php
            if ($editServers) {
                $serializedServers = [];
                foreach ($servers as $server) {
                    $serializedServers[] = [
                        'id' => $server->getID(),
                        'realmRootUrl' => $server->getRealmRootUrl(),
                        'clientID' => $server->getClientID(),
                        'clientSecret' => $server->getClientSecret(),
                        'registrationEnabled' => $server->isRegistrationEnabled(),
                        'logoutOnLogout' => $server->isLogoutOnLogout(),
                        'registrationGroupID' => $server->getRegistrationGroupID(),
                        'emailRegexes' => implode("\n", $server->getEmailRegexes()),
                        '_showPassword' => false,
                    ];
                }
                ?>
                const servers = <?= json_encode($serializedServers) ?>;
                if (servers.length === 0) {
                    servers.push(this.createNewServerData());
                }
                <?php
            } else {
                ?>
                const servers = null;
                <?php
            }
            ?>
            return {
                visible: <?= json_encode($ui->majorVersion >= 9 ? false : true) ?>,
                multiServerChecked: servers !== null && (servers.length !== 1 || servers[0].emailRegexes !== ''),
                servers,
            };
        },
        mounted() {
            const $form = $('#keycloakauth-config').closest('form');
            <?php
            if ($ui->majorVersion >= 9) {
                ?>
                const $enabled = $form.find('input[type="checkbox"][name="authentication_type_enabled"]');
                $enabled
                    .on('change', () => {
                        this.visible = $enabled.is(':checked');
                    })
                    .trigger('change')
                ;
                <?php
            }
            ?>
            $form.on('submit', (e) => {
                if (this.visible && !this.checkForm()) {
                    e.preventDefault();
                    return e.returnValue = false;
                }
            });
        },
        computed: {
            multiServer() {
                return this.servers === null ? false : this.canDisableMultiServer ? this.multiServerChecked : true;
            },
            canDisableMultiServer() {
                return this.servers !== null && this.servers.length === 1 && this.servers[0].emailRegexes === '';
            },
        },
        methods: {
            createNewServerData() {
                return {
                    id: null,
                    realmRootUrl: '',
                    clientID: '',
                    clientSecret: '',
                    registrationEnabled: true,
                    logoutOnLogout: false,
                    registrationGroupID: null,
                    emailRegexes: '',
                    _showPassword: false,
                };
            },
            newServer() {
                this.servers.push(this.createNewServerData());
            },
            splitRegexes(str) {
                if (typeof str !== 'string') {
                    return '';
                }
                return str.replace(/^\s+|\s+$/, '').replace(/\s*[\r\n]+\s*/g, '\n').split('\n').filter((s) => s !== '');
            },
            checkForm() {
                try {
                    if (this.servers !== null) {
                        if (this.servers.length === 0) {
                            throw <?= json_encode(t('Please specify at least one server.')) ?>;
                        }
                        this.servers.forEach((server, index) => {
                            this.checkServer(server);
                            if (this.splitRegexes(server.emailRegexes).length === 0) {
                                if (index !== this.servers.length - 1) {
                                    throw <?= json_encode('Only the last server can have an empty list of regular expressions.') ?>;
                                }
                            }
                        });
                    }
                } catch (e) {
                    window.ConcreteAlert.error({message: e.message || e?.toString() || '?'});
                    return false;
                }
                return true;
            },
            checkServer(server) {
                const serverPosition = 1 + this.servers.indexOf(server);
                if (server.realmRootUrl === '') {
                    throw <?= json_encode(t('Please specify the realm root URL of the server #%s')) ?>.replace('%s', serverPosition);
                }
                this.splitRegexes(server.emailRegexes).forEach((emailRegex) => this.checkEmailRegex(server, emailRegex));
            },
            checkEmailRegex(server, emailRegex) {
                const serverPosition = 1 + this.servers.indexOf(server);
                try {
                    new RegExp(emailRegex, 'i');
                } catch(e) {
                    throw <?= json_encode('Error checking the regular expression %1$s of the server %2$s: %3$s') ?>.replace(/%1\$s/, `"${emailRegex}"`).replace(/%2\$s/, serverPosition).replace(/%3\$s/, e.message || e.toString());
                }
            },
            removeServer(server, delta) {
                const index = this.servers.indexOf(server);
                if (index < 0) {
                    return;
                }
                this.servers.splice(index, 1);
            },
            moveServer(server, delta) {
                const oldIndex = this.servers.indexOf(server);
                if (oldIndex < 0) {
                    return;
                }
                const newIndex = oldIndex + delta;
                if (newIndex === oldIndex || newIndex < 0 || newIndex >= this.servers.length) {
                    return;
                }
                this.servers.splice(oldIndex, 1);
                this.servers.splice(newIndex, 0, server);
            },
        },
    });
}

function check()
{
    <?php
    if ($ui->majorVersion >= 9) {
        ?>
        if (window?.Concrete?.Vue?.activateContext) {
            window.Concrete.Vue.activateContext('cms', initialize);
            return;
        }
        <?php
    } else {
        ?>
        if (window.Vue) {
            initialize(window.Vue, {components: null});
            return;
        }
        <?php
    }
    ?>
    setTimeout(() => check(), 100);
}

check();

});
</script>
