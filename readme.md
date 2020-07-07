# EMS III LTI Plugin

__Project:__ [ttek-plg/plg-lti](http://packagist.org/packages/ttek-plg/plg-lti)  
__Published:__ 01 Sep 2016
__Web:__ <http://www.tropotek.com/>  
__Authors:__ Michael Mifsud <http://www.tropotek.com/>  
  
An lti Plugin for the new EMS III System. Use this to create your own plugins.

## Contents

- [Installation](#installation)
- [Introduction](#introduction)


## Installation

Available on Packagist ([ttek-plg/plg-lti](http://packagist.org/packages/ttek-plg/lti))
and as such installable via [Composer](http://getcomposer.org/).

```bash
# composer require ttek-plg/plg-lti
```

Or add the following to your composer.json file:

```json
{
  "ttek-plg/plg-lti": "~1.0"
}
```

If you do not use Composer, you can grab the code from GitHub, and use any
PSR-0 compatible autoloader (e.g. the [plg-lti](https://github.com/tropotek/plg-lti))
to load the classes.

## Introduction

NOTE: If you have issues with invalid timestamp you can try this:

    ALTER TABLE `_lti2_nonce` CHANGE `value` `value` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
    ALTER TABLE `_lti2_share_key` CHANGE `share_key_id` `share_key_id` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
    ALTER TABLE `_lti2_tool_proxy` CHANGE `tool_proxy_id` `tool_proxy_id` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

Sometimes this is an issue with newer MySQL databases

## Example LTI launch params

  Array[40]
(
  [tool_consumer_info_product_family_code] => Blackboard Learn
  [resource_link_title] => lti-voce II (NEW)
  [context_title] => VOCE Vet Science LTI test
  [roles] => urn:lti:role:ims/lis/Instructor
  [lis_person_name_family] => SampsonMifsud
  [tool_consumer_instance_name] => The University of Melbourne
  [tool_consumer_instance_guid] => 1005cc36f90e4ed58af938c5cea8374a
  [resource_link_id] => _74321_1
  [custom_testparam1] => testValue1
  [custom_testparam2] => testValue2
  [oauth_signature_method] => HMAC-SHA1
  [oauth_version] => 1.0
  [custom_caliper_profile_url] => https://unimelb.edu.au/learn/api/v1/telemetry/caliper/profile/_75691_1
  [launch_presentation_return_url] => https://unimelb.edu.au/webapps/blackboard/execute/blti/launchReturn?subject_id=_2051_1&content_id=_75691_1&toGC=false&launch_time=10000020541026&launch_id=b98984d5-1079-45d0-853c-71ce76643197&link_id=_75691_1
  [ext_launch_id] => b993834d5-1079-45d0-853c-71ce76643197
  [resource_link_description] => Test the New VOCE II site.
Now named PeerReView for public consumption. ;-)
NOTE: Requires my PC to be on to test...lol (If its down, I'm out.)
Also view the public part of the site that can be marketed to other Institutions or Faculties. (http://252s-dev.vet.unimelb.edu.au/~mifsudm/Unimelb/lti-voce2/)
  [ext_lms] => bb-3200.0.1-rel.56+af64d14
  [lti_version] => LTI-1p0
  [lis_person_contact_email_primary] => michael.mifsud@unimelb.edu.au
  [oauth_signature] => K0rJ442MLEQXTZhddNQWG85qumk=
  [tool_consumer_instance_description] => The University of Melbourne
  [oauth_consumer_key] => unimelb_00000
  [launch_presentation_locale] => en-AU
  [custom_caliper_federated_session_id] => https://blackboard.com/v1/sites/41943a4f-ec98-419c-8aa2-c7147a833858/sessions/ACE9B0DDB7EFBDC51162AF9B60BE0DDE
  [lis_person_sourcedid] => smaso
  [oauth_timestamp] => 1501020541
  [lis_person_name_full] => Samson Joe
  [tool_consumer_instance_contact_email] => dba-support@unimelb.edu.au
  [lis_person_name_given] => Samson
  [custom_tc_profile_url] =>
  [oauth_nonce] => 45778356062349877
  [lti_message_type] => basic-lti-launch-request
  [user_id] => e178575f054e46bffdaadfb1438d099b
  [oauth_callback] => about:blank
  [tool_consumer_info_version] => 3200.0.1-rel.56+af64d14
  [context_id] => 7cd5258c04e749a2d67d184f6f200328
  [context_label] => VOCE10001_2014_SM5
  [launch_presentation_document_target] => window
  [ext_launch_presentation_css_url] => https://unimelb.edu.au/common/shared.css,https://unimelb.edu.au/branding/themes/unimelb-201410-08/theme.css,https://unimelb.edu.au/branding/colorpalettes/unimelb-201404.08/generated/colorpalette.generated.modern.css
  [lti_subjectId] => 1

)