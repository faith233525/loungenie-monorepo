from ftplib import FTP

# FTP Configuration
FTP_HOST = "ftp.poolsafeinc.com"  # FTP server address
FTP_USER = "ftpadmin@loungenie.com"  # FTP username
FTP_PASS = "LounGenie21!"  # FTP password

# Connect to FTP Server
ftp = FTP(FTP_HOST)
ftp.login(user=FTP_USER, passwd=FTP_PASS)

# Print welcome message
print(ftp.getwelcome())

# List files and directories
ftp.retrlines('LIST')

# Close the connection
ftp.quit()