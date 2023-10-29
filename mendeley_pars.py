# -*- coding: utf-8 -*-
import requests
import pymysql
from pymysql.cursors import DictCursor
from sshtunnel import SSHTunnelForwarder
import json

client_id =123456
client_secret ='pass'
redirect_uri = 'http://'

url="https://api.mendeley.com/oauth/token"
payload={"grant_type":"client_credentials","scope":"all","client_id":"123456","client_secret":"pass"}
headers = {"Content-Type: application/x-www-form-urlencoded"}
response = requests.post( url,  data=payload)
 
bearer_dict = json.loads(response.text)
bearer=bearer_dict['access_token']

user_name='root'
secret='net.IPv6.all.forwar=1'

with SSHTunnelForwarder(
        ('10.10.10.10',22),
        ssh_username=user_name,
        ssh_password=secret,
        remote_bind_address=('localhost',3307)
        ) as server:
    with pymysql.connect(
                host='localhost',
                user=user_name,
                password=secret,
                db='rosa',
                charset='utf8mb4',
                cursorclass=DictCursor,
                port=server.local_bind_port
                ) as connection:
        
        with connection.cursor() as cursor:
            sql = "SELECT id_scopus FROM `user_scopus_orcid_id` "     
            cursor.execute(sql)
            connection.commit()
            
rows = cursor.fetchall()
for row in rows:
    scopus_id=row['id_scopus']
    url = "https://api.mendeley.com/profiles/v2?scopus_author_id="+scopus_id

    b="Bearer"+' '+bearer
    payload={}
    headers = {
     'Authorization': b
    }
     
    response = requests.request("GET", url, headers=headers, data=payload)
    author=json.loads(response.text)
    if len(author)>0:
        author_mid=author[0]['id']
        
        
        
        url_mendeley = "https://api.mendeley.com/catalog?author_profile_id="+author_mid
        payload={}
        headers = {
         'Authorization': b,
         'Access': 'application/vnd.mendeley-document.1+json'
        }
         
        response = requests.request("GET", url_mendeley, headers=headers, data=payload)
        if len(response.text)>0:
            pubs=json.loads(response.text)
        
            with SSHTunnelForwarder(
                        ('10.10.10.10',22),
                        ssh_username=user_name,
                        ssh_password=secret,
                        remote_bind_address=('localhost',3307)
                        ) as server:
                    with pymysql.connect(
                                host='localhost',
                                user=user_name,
                                password=secret,
                                db='dbname',
                                charset='utf8mb4',
                                cursorclass=DictCursor,
                                port=server.local_bind_port
                                ) as connection:
                        
                        with connection.cursor() as cursor:
                            for pub in pubs:
                                sql = "INSERT INTO `user_pub_source_19` (`bib_simhash`, `id_profile_r2`,`id_profile_source`,`page_pub`,`id_type`,`link_full_text`,`title`,`abstract`,`abstract_ru`,`bibzapis`,`classif`,`keywords`,`doi`,`subauthors`,`Y`,`cit`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                                if 'identifiers' in pub:
                                    if 'doi' in pub['identifiers']:
                                        doi=pub['identifiers']['doi']
                                    else:
                                        doi=''
                                if 'title' in pub:
                                    title=pub['title']
                                else:
                                    title=''
                                if 'year' in pub:
                                    year=int(pub['year'])
                                else:
                                    year=1950
                                if 'id' in pub:
                                    mendeley_id=pub['id']
                                else:
                                    mendeley_id=''
                                if 'authors' in pub:
                                    author=''
                                    for subaut in pub['authors']:
                                        if 'first_name' in subaut:
                                            author=author+subaut['first_name']+' '
                                        if 'last_name' in subaut:
                                            author=author+subaut['last_name']+', '
                                else:
                                    author=''
                                
                                if 'link' in pub:
                                    url_p=pub['link']
                                else:
                                    url_p=''
                                
                                if 'abstract' in pub:
                                    abstract=pub['abstract']
                                else:
                                    abstract=''
                                    
                                if 'keywords' in pub:
                                    keywords=''
                                    for i in range(len(pub['keywords'])):
                                        keywords=keywords+pub['keywords'][i]+'; '
                                else:
                                    keywords=''
                                    
                                    
                                cursor.execute(sql, ('','',author_mid,'',1,url_p,title,abstract,'','','',keywords,doi,author,year,1))
                            connection.commit() 

with SSHTunnelForwarder(
        ('10.10.10.10',22),
        ssh_username=user_name,
        ssh_password=secret,
        remote_bind_address=('localhost',3306)
        ) as server:
    with pymysql.connect(
                host='localhost',
                user=user_name,
                password=secret,
                db='rosa',
                charset='utf8mb4',
                cursorclass=DictCursor,
                port=server.local_bind_port
                ) as connection:
        
        with connection.cursor() as cursor:
            sql = "DELETE a.* FROM `user_pub_source_19` a,(SELECT b.bib_simhash, b.id_profile_r2, b.id_profile_source,b.page_pub, b.id_type, b.link_full_text,b.title,b.abstract,b.abstract_ru,b.bibzapis,b.classif, b.keywords, b.doi,b.subauthors,b.Y,b.cit, MIN(b.id) mid FROM user_pub_source_19 b GROUP BY b.bib_simhash, b.id_profile_r2, b.id_profile_source,b.page_pub, b.id_type, b.link_full_text,b.title,b.abstract,b.abstract_ru,b.bibzapis,b.classif, b.keywords, b.doi,b.subauthors,b.Y,b.cit) c\
                   WHERE a.bib_simhash = c.bib_simhash AND a.id_profile_r2 = c.id_profile_r2\
                   AND a.id_profile_source = c.id_profile_source\
                   AND a.page_pub = c.page_pub\
                   AND a.id_type = c.id_type\
                   AND a.link_full_text = c.link_full_text\
                   AND a.title = c.title\
                   AND a.abstract = c.abstract\
                   AND a.abstract_ru = c.abstract_ru\
                   AND a.bibzapis = c.bibzapis\
                   AND a.classif = c.classif\
                   AND a.keywords = c.keywords\
                   AND a.doi = c.doi\
                   AND a.subauthors = c.subauthors\
                   AND a.Y = c.Y\
                   AND a.cit = c.cit\
                   AND a.id > c.mid"     
            cursor.execute(sql)
            connection.commit()    

