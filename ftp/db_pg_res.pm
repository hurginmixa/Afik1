package db_pg_res;

use strict 'vars';
use Pg;

sub new
{
    my $class      = shift;
    my $cursor     = shift;
    return undef if (!defined($class));
    return undef if (!defined($cursor));

    my $self = {cursor => $cursor};

    $class = ref($class) || $class;
    bless ($self, $class);

    $self->Top();

    return $self;
}


sub Top()
{
    my($self) = @_;

    $self->{pos} = 0;
}


sub isError()
{
    my($self) = @_;

    if ($self->{cursor} == -1) {
        return 1;
    }

    return ($self->{cursor}->resultStatus != PGRES_COMMAND_OK && $self->{cursor}->resultStatus != PGRES_TUPLES_OK) ? 1 : 0;
}


sub Status()
{
    my($self) = @_;

    return $self->{cursor}->resultStatus;
}


sub NumRows()
{
    my($self) = @_;

    if ($self->isError) {
        return 0;
    }

    return $self->{cursor}->ntuples;
}


sub Eof()
{
    my($self) = @_;

    return $self->NumRows <= $self->{pos} ? 1 : 0;
}


sub Next()
{
    my($self) = @_;

    if (!$self->Eof) {
        $self->{pos}++;
    }

    return !$self->Eof;
}


sub Value($)
{
    my($self, $field) = @_;

    if ($self->isError || $self->Eof) {
        return "";
    }

    my($nfield) = $self->{cursor}->fnumber($field);

    return ($nfield != -1) ? ($self->{cursor}->getvalue($self->{pos}, $nfield)) : "";
}

1
