#!/usr/bin/perl
#
# FYR/AbuseChecks.pm:
# Some automated abuse checks.
#
# Copyright (c) 2004 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: AbuseChecks.pm,v 1.3 2004-12-16 18:33:22 francis Exp $
#

package FYR::AbuseChecks;

use strict;

use Net::Google::Search;
use Data::Dumper;

use mySociety::Config;

# google_for_postcode POSTCODE
# Return true if the POSTCODE occurs with the terms "faxyourmp" or
# "writetothem" on a web page indexed by Google.
sub google_for_postcode ($) {
    my ($pc) = @_;
    our ($G, $nogoogle);
    if (!$G) {
        my $key = mySociety::Config::get('GOOGLE_API_KEY', "");
        if ($key ne "") {
            $G = new Net::Google::Search({key => $key});
        }
        $nogoogle = 1 unless ($G);
    }
    return 0 if ($nogoogle);

    $pc =~ s#\s##g;
    $pc = uc($pc);

    # We need to put the space back in in the right place now.
    # http://www.govtalk.gov.uk/gdsc/html/noframes/PostCode-2-1-Release.htm
    my $pc2 = $pc;
    $pc2 =~ s#(\d[A-Z]{2})# $1#;
    
    # ("-site:mysociety.org" is a hack to stop it finding (e.g.) my postcode in
    # checked-in code in CVSTrac.... --chris 20041215)
    my $googlesearch = sprintf('%s OR "%s" writetothem OR faxyourmp -site:mysociety.org', $pc, $pc2);
    $G->query('', $googlesearch);
    my $results = $G->results();
    #warn Dumper($results);
    #warn $googlesearch;
    #warn scalar(@$results);

    return (scalar(@$results) > 0);
}

=item test MESSAGE

Perform abuse checks on the MESSAGE (hash of database fields). This returns in
list context one of: 'ok' to indicate that delivery should occur as normal,
'hold' to indicate that the message should be held for inspection by an
administrator, or 'reject' to indicate that the message should be discarded; 
the reason for the admin for the result.

=cut
sub test ($) {
    my ($msg) = @_;

    # Debug options
    if ($msg->{message} =~ m/ABUSETESTHOLD/) {
        return ('hold', 'ABUSETESTHOLD present in message body');
    }
    if ($msg->{message} =~ m/ABUSETESTREJECT/) {
        return ('reject', 'ABUSETESTREJECT present in message body');
    }

    # See if postcode mentioned on interweb
    return ('hold', 'Postcode appears in Google with term "faxyourmp" or "writetothem"') if (google_for_postcode($msg->{sender_postcode}));

    # XXX flesh out with dynamic anti-spam rules

    return ('ok', undef);
}

1;