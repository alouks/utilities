import base64
import hashlib
from Crypto import Random
from Crypto.Cipher import AES

'''
Encryption

@description     For arbitrary encryption and decryption of data
@author          Aaron Louks

Usage:

    e = Encryption()
    encrypted_string = e.encrypt("Encrypt me!", "password")
    decrypted = e.decrypt(encrypted_string, "password")

'''

class Encryption:

	def __init__(self):
		self.bs = 16

	def encrypt(self, plaintext, password):
		plaintext = self.pad(plaintext)
		iv = Random.new().read(self.bs)
		key = hashlib.sha256(password).hexdigest()[:32]
		cipher = AES.new(key, AES.MODE_CBC, iv)
		return base64.b64encode(iv + cipher.encrypt(plaintext))

	def decrypt(self, ciphertext, password):
		key = hashlib.sha256(password).hexdigest()[:32]
		ciphertext = base64.b64decode(ciphertext)
		iv = ciphertext[:16]
		cipher = AES.new(key, AES.MODE_CBC, iv)
		decrypt = self.unpad(cipher.decrypt(ciphertext[16:]))
		return decrypt

	def pad(self, s):
		return s + (self.bs - len(s) % self.bs) * chr(self.bs - len(s) % self.bs)

	def unpad(self, s):
		return s[:-ord(s[len(s)-1:])]
