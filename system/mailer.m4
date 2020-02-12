PUSHDIVERT(-1)

#
# Afik1's Mailer 2003
#
#
#

define(p1, `$1')
define(p2, `$2')

define(`_NAME_', `p1(_ARGS_)')
define(`_DIR_', `p2(_ARGS_)')

POPDIVERT
M`'_NAME_,	P=_DIR_/mailprog, F=sDFMPhnu9ScdE, S=EnvFromSMTP/HdrFromSMTP, R=EnvToSMTP,
		D=_DIR_, T=DNS/RFC822/X-Unix, U=apache,
		A=mailprog $h $u

