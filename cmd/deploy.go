package cmd

import (
	"github.com/ryangjchandler/forge-previewer/forge"
	"github.com/urfave/cli/v2"
)

var (
	token string
)

func Deploy(c *cli.Context) error {
	forge.SetForgeToken(c.String("token"))
	forge.SetRepo(c.String("repo"))
	forge.SetBranch(c.String("branch"))

	return nil
}

func GetDeployCommand() *cli.Command {
	return &cli.Command{
		Name:    "deploy",
		Aliases: []string{"d"},
		Usage:   "Deploy your pull request to Laravel Forge.",
		Flags: []cli.Flag{
			&cli.StringFlag{
				Name:     "token",
				Usage:    "Your Forge API token.",
				Required: true,
			},
			&cli.StringFlag{
				Name:     "repo",
				Usage:    "The name of your repository.",
				Required: true,
			},
			&cli.StringFlag{
				Name:     "branch",
				Usage:    "The name of your branch.",
				Required: true,
			},
		},
		Action: Deploy,
	}
}
