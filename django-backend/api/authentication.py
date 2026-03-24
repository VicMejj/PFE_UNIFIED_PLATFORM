import jwt
import os
from django.conf import settings
from rest_framework.authentication import BaseAuthentication
from rest_framework.exceptions import AuthenticationFailed


class LaravelJWTAuthentication(BaseAuthentication):
    """
    Custom DRF authentication class that validates JWT tokens
    issued by the Laravel tymon/jwt-auth package.
    The secret key MUST match the JWT_SECRET in Laravel's .env file.
    """

    def authenticate(self, request):
        auth_header = request.headers.get('Authorization')

        if not auth_header or not auth_header.startswith('Bearer '):
            return None  # Let other authenticators try, or deny

        token = auth_header.split(' ')[1]

        try:
            # Prefer an explicit environment variable so deployments can override it,
            # but fall back to the Django settings JWT_SECRET (which defaults to the
            # same value as Laravel's .env in this repo).
            secret = os.environ.get('JWT_SECRET', getattr(settings, 'JWT_SECRET', 'Nzs4n58jW9QGXNMC2Yrrpji8nvzlzuji5VCX0YoIdUsN6ttES7SG9jkksI6SvbvX'))
            payload = jwt.decode(
                token,
                secret,
                algorithms=['HS256'],
                options={"verify_aud": False}
            )
            return (payload, token)  # (user, auth)
        except jwt.ExpiredSignatureError:
            raise AuthenticationFailed('Token has expired.')
        except jwt.InvalidTokenError as e:
            raise AuthenticationFailed(f'Invalid token: {str(e)}')

    def authenticate_header(self, request):
        return 'Bearer realm="django-ai-api"'
