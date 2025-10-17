<h1>EVERY MEMBER HAS TO OPEN A NEW BRANCH!</h1>
<BR>
<h2>EVERY BRANCH HAS TO BE PUSHED FROM YOUR LOCAL REPO TO THE REMOTE REPO</h2>
<h3> REMEMBER TO PROVIDE A SIMPLE DESCRIPTION FOR YOUR GOALS WHEN CREATING THE BRANCH IN THE FIRST COMMIT</h3>
TO BUILD THE CONTAINERS NETWORK : 
<br>
1 - AFTER CLONNING THE GIT DIRECTORY, TRAVERSE TO THE DOCKER-COMPOSE.YML FILE DIRECTORY
<br>
2 - OPEN A NEW TERMINAL AND RUN THE FOLLOWING COMMAND : 
<br>
    "docker-compose up -d" (IF USING V2 U CAN TYPE docker compose instead of docker-compose if it triggered an error)
<br>
3 - FOR TERMINATION, USE : 
<br>
    "docker-compose stop" 
<br>
<h2>IN CASE OF ANY CHANGES TO THE BUILD FILES, NOTIFY ME</h2>
<br>
<h2>REMEMBER TO ALWAYS PULL THE NEW CHANGES FROM THE MAIN BRANCH TO ENSURE HAVING THE BASIC BUILDS</h2>
<br>
<h1>Below are the testing procedures that we shall be implementing</h1>
<h3>1 - CRAWLING</h3>
<p>First and most basic technique we should use is crawling the URL provided by the user, this covers the whole website structure, which we can later store in variables to have our tools scan them one by one, I think this is an essential step since it will outline all the input fields present in the URL so we can test them against a majority of the Web vulnerabilities which targets the input fields.
This is just one example and use of the info provided by the crawlers. Additional consideration towards this technique will be taken once new ideas arrive, so we implement them in the logic of the crawler from the beginning</p>
