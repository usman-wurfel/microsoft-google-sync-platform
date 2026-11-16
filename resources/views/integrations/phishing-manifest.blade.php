<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<OfficeApp
          xmlns="http://schemas.microsoft.com/office/appforoffice/1.1"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xmlns:bt="http://schemas.microsoft.com/office/officeappbasictypes/1.0"
          xmlns:mailappor="http://schemas.microsoft.com/office/mailappversionoverrides"
          xsi:type="MailApp">
  <!-- Fixed UUID - configure via OUTLOOK_ADDIN_ID in .env or use this default -->
  <Id>{{ env('OUTLOOK_ADDIN_ID', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890') }}</Id>
  <Version>1.0.0.0</Version>
  <ProviderName>{{ config('app.name', 'WebExert Sync') }}</ProviderName>
  <DefaultLocale>en-US</DefaultLocale>
  <DisplayName DefaultValue="Report Phishing" />
  <Description DefaultValue="Report suspicious emails to your security team." />
  <IconUrl DefaultValue="{{ url('/images/phishing-icon-64.svg') }}" />
  <HighResolutionIconUrl DefaultValue="{{ url('/images/phishing-icon-128.svg') }}" />
  <SupportUrl DefaultValue="{{ url('/support') }}" />
  <AppDomains>
    <AppDomain>{{ url('/') }}</AppDomain>
  </AppDomains>
  <Hosts>
    <Host Name="Mailbox" />
  </Hosts>
  <Requirements>
    <Sets>
      <Set Name="Mailbox" MinVersion="1.1" />
    </Sets>
  </Requirements>
  <FormSettings>
    <Form xsi:type="ItemRead">
      <DesktopSettings>
        <SourceLocation DefaultValue="{{ url('/phishing-addin') }}" />
        <RequestedHeight>250</RequestedHeight>
      </DesktopSettings>
    </Form>
  </FormSettings>
  <Permissions>ReadWriteItem</Permissions>
  <Rule xsi:type="RuleCollection" Mode="Or">
    <Rule xsi:type="ItemIs" ItemType="Message" FormType="Read" />
  </Rule>
  <DisableEntityHighlighting>false</DisableEntityHighlighting>
  <VersionOverrides xmlns="http://schemas.microsoft.com/office/mailappversionoverrides" xsi:type="VersionOverridesV1_0">
    <Hosts>
      <Host xsi:type="MailHost">
        <DesktopFormFactor>
          <ExtensionPoint xsi:type="MessageReadCommandSurface">
            <OfficeTab id="TabDefault">
              <Group id="msgReadGroup">
                <Label resid="groupLabel" />
                <Control xsi:type="Button" id="msgReadOpenPaneButton">
                  <Label resid="paneReadButtonLabel" />
                  <Supertip>
                    <Title resid="paneReadSuperTipTitle" />
                    <Description resid="paneReadSuperTipDescription" />
                  </Supertip>
                  <Icon>
                    <bt:Image size="16" resid="icon16" />
                    <bt:Image size="32" resid="icon32" />
                    <bt:Image size="80" resid="icon80" />
                  </Icon>
                  <Action xsi:type="ShowTaskpane">
                    <SourceLocation resid="taskpaneUrl" />
                  </Action>
                </Control>
              </Group>
            </OfficeTab>
          </ExtensionPoint>
        </DesktopFormFactor>
      </Host>
    </Hosts>
    <Resources>
      <bt:Images>
        <bt:Image id="icon16" DefaultValue="{{ url('/images/phishing-icon-16.svg') }}" />
        <bt:Image id="icon32" DefaultValue="{{ url('/images/phishing-icon-32.svg') }}" />
        <bt:Image id="icon80" DefaultValue="{{ url('/images/phishing-icon-80.svg') }}" />
      </bt:Images>
      <bt:Urls>
        <bt:Url id="taskpaneUrl" DefaultValue="{{ url('/phishing-addin') }}" />
      </bt:Urls>
      <bt:ShortStrings>
        <bt:String id="groupLabel" DefaultValue="Security" />
        <bt:String id="paneReadButtonLabel" DefaultValue="Report Phishing" />
        <bt:String id="paneReadSuperTipTitle" DefaultValue="Report Phishing" />
      </bt:ShortStrings>
      <bt:LongStrings>
        <bt:String id="paneReadSuperTipDescription" DefaultValue="Report this email as phishing and move it to junk." />
      </bt:LongStrings>
    </Resources>
  </VersionOverrides>
</OfficeApp>
