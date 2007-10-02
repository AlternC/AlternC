
sub dump_links {
    my $mech = shift;
    for my $link ( $mech->links ) {
        my $url = $absolute ? $link->url_abs : $link->url;
        print "$url\n";
    }
}

sub dump_images {
    my $mech = shift;

    for my $image ( $mech->images ) {
        my $url = $absolute ? $image->url_abs : $image->url;
        print "$url\n";
    }
}

sub dump_forms {
    my $mech = shift;

    for my $form ( $mech->forms() ) {
        print $form->dump;
        print "\n";
    }
}

1;

