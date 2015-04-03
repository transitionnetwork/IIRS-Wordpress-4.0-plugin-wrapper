<?php
// this code can be saved in to the Wordpress plugin and WPML will automatically pick up the translations.
// some texts appear in multiple files. and often the files are utitlity files, rather than the actual URL for the appropriate screen.

// --------------------------------- , IIRS_common/akismet.php
__( 'Failed to check the entries against the Akismet SPAM database. Please try again tomorrow :)', 'iirs' ); //IIRS_common/akismet.php

// --------------------------------- , IIRS_common/documentation/domain-checking.php
__( 'nice_domains', 'iirs' ); //IIRS_common/documentation/domain-checking.php, IIRS_common/utility.php
__( 'nice_tlds', 'iirs' ); //IIRS_common/documentation/domain-checking.php, IIRS_common/utility.php

// --------------------------------- EDIT SCREEN, IIRS_common/edit/index.php
__( 'no towns found matching', 'iirs' ); //IIRS_common/edit/index.php, IIRS_common/registration/location_general.php
__( 'you will need to email', 'iirs' ); //IIRS_common/edit/index.php
__( 'to register by email because we cannot find your town on our maps system!', 'iirs' ); //IIRS_common/edit/index.php
__( 'setup editor', 'iirs' ); //IIRS_common/edit/index.php
__( 'your details', 'iirs' ); //IIRS_common/edit/index.php
__( 'update account details', 'iirs' ); //IIRS_common/edit/index.php
__( 'transition initiative details', 'iirs' ); //IIRS_common/edit/index.php
__( 'change location', 'iirs' ); //IIRS_common/edit/index.php
__( 'search', 'iirs' ); //IIRS_common/edit/index.php
__( 'update transition initiative', 'iirs' ); //IIRS_common/edit/index.php
__( 'login required to edit', 'iirs' ); //IIRS_common/edit/index.php
__( 'There is no Initiative associated with this user', 'iirs' ); //IIRS_common/edit/index.php, IIRS_common/view/index.php

// --------------------------------- REGISTRATION EMAIL, IIRS_common/framework_abstraction_layer.php
__( 'your new Transition account', 'iirs' ); //IIRS_common/framework_abstraction_layer.php
__( 'welcome to Transition', 'iirs' ); //IIRS_common/framework_abstraction_layer.php
__( 'here are your registration details', 'iirs' ); //IIRS_common/framework_abstraction_layer.php
__( 'username', 'iirs' ); //IIRS_common/framework_abstraction_layer.php
__( 'password', 'iirs' ); //IIRS_common/framework_abstraction_layer.php
__( 'reply to this email with any thoughts / excitement / ideas / congratulations / bugs / other things :)', 'iirs' ); //IIRS_common/framework_abstraction_layer.php

// --------------------------------- Registration SCREEN #2, IIRS_common/location.php
__( 'view on map', 'iirs' ); //IIRS_common/location.php
__( 'transition initiative not registered yet!', 'iirs' ); //IIRS_common/location.php
__( 'Oops, it seems that the our servers are not responding! The manager has been informed and is trying to solve the problem. Please come back here tomorrow :)', 'iirs' ); //IIRS_common/location.php, IIRS_common/location_providers/google.php, IIRS_common/location_providers/mapquest.php, IIRS_common/location_providers/openstreetmap.php, class-iirs.php, class-iirs.php

// --------------------------------- MAPPING SCREEN, IIRS_common/mapping/index.php
__( 'Oops, Javascript failed to run, services unavailable, please go to', 'iirs' ); //IIRS_common/mapping/index.php, IIRS_common/registration/index.php
__( 'to register instead', 'iirs' ); //IIRS_common/mapping/index.php, IIRS_common/registration/index.php
__( 'map of Transition Initiatives near you', 'iirs' ); //IIRS_common/mapping/index.php

// --------------------------------- Registration SCREEN #3, IIRS_common/registration/domain_selection.php
__( '[IIRS admin notice] new Transition account registered', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'view in new window', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'you are now registered.', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'is go!', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'you will need to log in to', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'to manage your registration, NOT this website', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'Here are the websites we have found that might correspond to your initiative. We invite you to select one; complete the "other" field or choose the option "no wesbite".', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'no website', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'we do not currently have a website', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'other', 'iirs' ); //IIRS_common/registration/domain_selection.php, IIRS_common/registration/location_general.php
__( 'your website', 'iirs' ); //IIRS_common/registration/domain_selection.php
__( 'back', 'iirs' ); //IIRS_common/registration/domain_selection.php, IIRS_common/registration/summary_projects.php
__( 'save and continue', 'iirs' ); //IIRS_common/registration/domain_selection.php

// --------------------------------- Registration SCREEN #1 (usually a sidebar WIDGET), IIRS_common/registration/index.php
__( '(why register link. format: http://[web address] [link text])', 'iirs' ); //IIRS_common/registration/index.php
__( 'register your Transition Initiative', 'iirs' ); //IIRS_common/registration/index.php
__( 'town or area', 'iirs' ); //IIRS_common/registration/index.php
__( 'register', 'iirs' ); //IIRS_common/registration/index.php
__( 'http://www.transitionnetwork.org/support/becoming-official#criteria', 'iirs' ); //IIRS_common/registration/index.php
__( 'what is a Transition Initiative?', 'iirs' ); //IIRS_common/registration/index.php
__( 'connect to the Transition Network and advertise yourself on our website.', 'iirs' ); //IIRS_common/registration/index.php

// --------------------------------- Registration SCREEN #2, IIRS_common/registration/location_general.php
__( 'this looks like a domain ( website address ), you need to enter a town or area name instead', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'We have found your town or area. However, the Initiative name already exists', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'Please add something to the initiative name below to make it unique.', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'For Example:', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'join network', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'We have found:', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'select this option to register without "geo-location"', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'change the search', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'registration of your Transition Initiative', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'initiative name', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'Transition Initiative', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'email', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'your name', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'This email address may be used by people in your area who would like to contact you and / or join your projects.', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'and then connect with local Transition Initiatives : )', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'this means that we won\'t know actually where your town is so it won\'t appear on the maps yet.', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'we will contact you to resolve this, or you can type in another name below.', 'iirs' ); //IIRS_common/registration/location_general.php
__( 'Oops, we didn\'t recieve your data. Please try again', 'iirs' ); //IIRS_common/registration/location_general.php

// --------------------------------- Registration SCREEN #4, IIRS_common/registration/summary_projects.php
__( 'summary', 'iirs' ); //IIRS_common/registration/summary_projects.php
__( 'from the website', 'iirs' ); //IIRS_common/registration/summary_projects.php
__( 'complete registration', 'iirs' ); //IIRS_common/registration/summary_projects.php
__( 'Your website was not found, please re-enter it or select "No Website"', 'iirs' ); //IIRS_common/registration/summary_projects.php

// --------------------------------- , IIRS_common/translations_js.php
__( 'system error', 'iirs' ); //IIRS_common/translations_js.php
__( 'form not valid', 'iirs' ); //IIRS_common/translations_js.php
__( 'email address not valid format', 'iirs' ); //IIRS_common/translations_js.php
__( 'a website selection option is required', 'iirs' ); //IIRS_common/translations_js.php
__( 'view full profile', 'iirs' ); //IIRS_common/translations_js.php

// --------------------------------- , IIRS_common/utility.php
__( 'continue', 'iirs' ); //IIRS_common/utility.php
__( 'We think you are a SPAM robot. please email us to resolve this issue.', 'iirs' ); //IIRS_common/utility.php
__( 'You have already registered a Transition Initiative under this username. Please logout and re-register', 'iirs' ); //IIRS_common/utility.php
__( 'A Transition Initiative already exists with this name. Please add something to the name or change it and try again', 'iirs' ); //IIRS_common/utility.php
__( 'Akismet thinks that your entry is SPAM. So we cannot accept it. Sorry.', 'iirs' ); //IIRS_common/utility.php

// --------------------------------- VIEW FULL PROFILE, IIRS_common/view/index.php
__( 'currently no website', 'iirs' ); //IIRS_common/view/index.php
__( 'website', 'iirs' ); //IIRS_common/view/index.php

// --------------------------------- , class-iirs.php
__( 'TransitionTowns registration service', 'iirs' ); //class-iirs.php
__( 'registrar@transitionnetwork.org', 'iirs' ); //class-iirs.php
__( 'IIRS edit', 'iirs' ); //class-iirs.php
__( 'IIRS export', 'iirs' ); //class-iirs.php
__( 'IIRS list', 'iirs' ); //class-iirs.php
__( 'IIRS mapping', 'iirs' ); //class-iirs.php
__( 'IIRS registration', 'iirs' ); //class-iirs.php
__( 'IIRS view', 'iirs' ); //class-iirs.php
// variable detected: __( 'IIRS $widget_folder', 'iirs' ); //class-iirs.php
__( 'There is already a user with this email or username. Please try again.', 'iirs' ); //class-iirs.php
__( 'Oops, Could not create your user account because of a system error. The manager has been informed and is trying to solve the problem. Please try again tomorrow.', 'iirs' ); //class-iirs.php
__( 'Could not delete the recently added user to allow re-addtion', 'iirs' ); //class-iirs.php
__( 'Could not logout and delete the current user because no current user was found to allow re-addtion. This might cause problems when trying again', 'iirs' ); //class-iirs.php
__( 'Failed to update your user details. Please try again tomorrow.', 'iirs' ); //class-iirs.php
__( 'Login Failed', 'iirs' ); //class-iirs.php
__( 'Page not found', 'iirs' ); //class-iirs.php
__( 'Initiative Facilitator', 'iirs' ); //class-iirs.php
__( 'view your transition initiative', 'iirs' ); //class-iirs.php
__( 'edit your transition initiative', 'iirs' ); //class-iirs.php
__( 'Add New', 'iirs' ); //class-iirs.php
__( 'No initiatives found.', 'iirs' ); //class-iirs.php
__( 'No initiatives found in Trash.', 'iirs' ); //class-iirs.php
?>