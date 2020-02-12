#!/bin/sh

echo -e \
"To: mixa@afik1.com\n"\
"From: sender\n"\
"Content-Type: application/x-tar\n"\
"Content-Transfer-Encoding: base64\n"\
"Content-Disposition: attachment; filename=\"sourse.tar.tz\"\n\n" > /tmp/sendsourse0_$$


tar cz * | mimencode > /tmp/sendsourse1_$$


cat /tmp/sendsourse0_$$ /tmp/sendsourse1_$$ | /usr/sbin/sendmail mixa@afik1.com
